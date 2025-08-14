<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();

// Initialize variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get monthly sales data
$monthly_sales = [];
$sql = "SELECT 
            DATE_FORMAT(sale_date, '%Y-%m') as month,
            MIN(DATE(sale_date)) as month_start,
            MAX(DATE(sale_date)) as month_end,
            SUM(total_amount) as total_sales
        FROM sales 
        WHERE sale_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
        ORDER BY month_start DESC";

if($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ss", $start_date, $end_date);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $monthly_sales[] = $row;
        }
        $result->free();
    }
    $stmt->close();
}

// Get monthly expenses data
$monthly_expenses = [];
$sql = "SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            MIN(DATE(expense_date)) as month_start,
            MAX(DATE(expense_date)) as month_end,
            SUM(amount) as total_expenses
        FROM expenses 
        WHERE expense_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
        ORDER BY month_start DESC";

if($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ss", $start_date, $end_date);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $monthly_expenses[] = $row;
        }
        $result->free();
    }
    $stmt->close();
}

// Combine data
$monthly_data = [];
foreach($monthly_sales as $sale) {
    $monthly_data[$sale['month']] = [
        'month_start' => $sale['month_start'],
        'month_end' => $sale['month_end'],
        'total_sales' => $sale['total_sales'],
        'total_expenses' => 0,
        'profit' => $sale['total_sales']
    ];
}

foreach($monthly_expenses as $expense) {
    if(isset($monthly_data[$expense['month']])) {
        $monthly_data[$expense['month']]['total_expenses'] = $expense['total_expenses'];
        $monthly_data[$expense['month']]['profit'] -= $expense['total_expenses'];
    } else {
        $monthly_data[$expense['month']] = [
            'month_start' => $expense['month_start'],
            'month_end' => $expense['month_end'],
            'total_sales' => 0,
            'total_expenses' => $expense['total_expenses'],
            'profit' => -$expense['total_expenses']
        ];
    }
}

// Sort by month start date
usort($monthly_data, function($a, $b) {
    return strtotime($b['month_start']) - strtotime($a['month_start']);
});

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Reports</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Monthly Reports</h1>
        
        <div class="filter-section">
            <form method="get">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="monthly.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
        
        <table class="reports-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Sales</th>
                    <th>Expenses</th>
                    <th>Profit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($monthly_data as $month): ?>
                <tr>
                    <td><?php echo date('F Y', strtotime($month['month_start'])); ?></td>
                    <td>₱<?php echo number_format($month['total_sales'], 2); ?></td>
                    <td>₱<?php echo number_format($month['total_expenses'], 2); ?></td>
                    <td class="<?php echo $month['profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                        ₱<?php echo number_format($month['profit'], 2); ?>
                    </td>
                    <td>
                        <a href="monthly_detail.php?month=<?php echo date('Y-m', strtotime($month['month_start'])); ?>" class="btn btn-sm">Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="action-buttons">
            <a href="../dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>