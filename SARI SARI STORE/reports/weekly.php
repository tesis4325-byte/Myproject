<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();

// Initialize variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get weekly sales data
$weekly_sales = [];
$sql = "SELECT 
            YEARWEEK(sale_date) as week_number,
            MIN(DATE(sale_date)) as week_start,
            MAX(DATE(sale_date)) as week_end,
            SUM(total_amount) as total_sales
        FROM sales 
        WHERE sale_date BETWEEN ? AND ?
        GROUP BY YEARWEEK(sale_date)
        ORDER BY week_start DESC";

if($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ss", $start_date, $end_date);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $weekly_sales[] = $row;
        }
        $result->free();
    }
    $stmt->close();
}

// Get weekly expenses data
$weekly_expenses = [];
$sql = "SELECT 
            YEARWEEK(expense_date) as week_number,
            MIN(DATE(expense_date)) as week_start,
            MAX(DATE(expense_date)) as week_end,
            SUM(amount) as total_expenses
        FROM expenses 
        WHERE expense_date BETWEEN ? AND ?
        GROUP BY YEARWEEK(expense_date)
        ORDER BY week_start DESC";

if($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ss", $start_date, $end_date);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $weekly_expenses[] = $row;
        }
        $result->free();
    }
    $stmt->close();
}

// Combine data
$weekly_data = [];
foreach($weekly_sales as $sale) {
    $weekly_data[$sale['week_number']] = [
        'week_start' => $sale['week_start'],
        'week_end' => $sale['week_end'],
        'total_sales' => $sale['total_sales'],
        'total_expenses' => 0,
        'profit' => $sale['total_sales']
    ];
}

foreach($weekly_expenses as $expense) {
    if(isset($weekly_data[$expense['week_number']])) {
        $weekly_data[$expense['week_number']]['total_expenses'] = $expense['total_expenses'];
        $weekly_data[$expense['week_number']]['profit'] -= $expense['total_expenses'];
    } else {
        $weekly_data[$expense['week_number']] = [
            'week_start' => $expense['week_start'],
            'week_end' => $expense['week_end'],
            'total_sales' => 0,
            'total_expenses' => $expense['total_expenses'],
            'profit' => -$expense['total_expenses']
        ];
    }
}

// Sort by week start date
usort($weekly_data, function($a, $b) {
    return strtotime($b['week_start']) - strtotime($a['week_start']);
});

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>New Sales</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/edit.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
<style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;         
        } 

        .summary-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;   
        } 

       .summary-card h3 {
            margin-top: 0;
        }

       .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        } 

       .sales-table th, .sales-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        } 

       .sales-table th {
            background-color: #f2f2f2;
        }

       .action-buttons {
            margin-top: 20px;
        }

       .btn {
       } 
    </style>
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <i class="fas fa-store-alt"></i>
        <span>Sari-Sari Store</span>
      </div>
      <nav class="sidebar-nav">
      <ul>
      <li><a href="../dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                <li class="active"><a href="../inventory/list.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="../sales/new_sale.php"><i class="fas fa-cash-register"></i> New Sale</a></li>
                <li><a href="../expenses/add.php"><i class="fas fa-receipt"></i> Add Expense</a></li>
                <li><a href="../reports/weekly.php"><i class="fas fa-chart-line"></i> Reports</a></li>
               
                <li>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
      </nav>
    </aside>
    <div class="container">
        <h1>Daily Reports</h1>
        
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
                <a href="weekly.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
        
        <table class="reports-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Sales</th>
                    <th>Expenses</th>
                    <th>Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($weekly_data as $week): ?>
                <tr>
                    <td><?php echo date('M j', strtotime($week['week_start'])) . ' - ' . date('M j, Y', strtotime($week['week_end'])); ?></td>
                    <td>₱<?php echo number_format($week['total_sales'], 2); ?></td>
                    <td>₱<?php echo number_format($week['total_expenses'], 2); ?></td>
                    <td class="<?php echo $week['profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                        ₱<?php echo number_format($week['profit'], 2); ?>
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