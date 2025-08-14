<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();

// Initialize variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Get expenses
$expenses = [];
$sql = "SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ?";
$params = [$start_date, $end_date];
$types = "ss";

if(!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY expense_date DESC";

if($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param($types, ...$params);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $expenses[] = $row;
        }
        $result->free();
    }
    $stmt->close();
}

// Calculate total expenses
$total_expenses = 0;
foreach($expenses as $expense) {
    $total_expenses += $expense['amount'];
}

// Get categories for dropdown
$categories = [];
$sql = "SELECT DISTINCT category FROM expenses ORDER BY category";
if($result = $mysqli->query($sql)) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    $result->free();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses Report</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/edit.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
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
                <li><a href="http://localhost/SARI%20SARI%20STORE/debts/list.php"><i class="fas fa-file-invoice-dollar"></i> Debts</a></li>

                <li>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
      </nav>
    </aside>
<div class="container fade-in">
    <h1>Expenses Report</h1>

    <div class="form-container">
        <form method="get" class="grid-form">
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Filter</button>
                <a href="report.php" class="btn logout-btn">Reset</a>
            </div>
        </form>
    </div>

    <div class="report-card">
        <h3>Total Expenses: ₱<?php echo number_format($total_expenses, 2); ?></h3>
    </div>

    <table class="inventory-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
                <th>Category</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $expense): ?>
            <tr>
                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($expense['category']); ?></td>
                <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="action-buttons">
        <a href="add.php" class="btn">Add Expense</a>
        <a href="../dashboard.php" class="btn logout-btn">Back to Dashboard</a>
    </div>
</div>

</body>
</html>