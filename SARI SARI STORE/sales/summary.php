<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();

// Get latest sales
$sales = [];
$sql = "SELECT 
    GROUP_CONCAT(CONCAT(p.product_name, ' x', s.quantity) SEPARATOR ', ') AS products,
    SUM(s.quantity) AS total_quantity,
    SUM(s.total_amount) AS total_amount,
    MAX(p.price) AS unit_price,
    MAX(s.cash_amount) AS cash_amount,
    (MAX(s.cash_amount) - SUM(s.total_amount)) AS change_amount,
    MAX(s.sale_date) AS sale_date
FROM sales s
JOIN products p ON s.product_id = p.id
GROUP BY s.cash_amount, s.sale_date
ORDER BY sale_date DESC
LIMIT 20;
";
// Ensure cash_amount and change_amount are properly retrieved from the database
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    $result->free();
}

// Calculate total sales
$total_sales = 0;
foreach ($sales as $sale) {
    $total_sales += $sale['total_amount'];
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sales Summary</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
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
            max-width: 1000px;
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
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
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
</head>
<body>
            <div class="container">
                <h1>Sales Summary</h1>

                <div class="summary-card">
                    <h3>Total Sales: ₱<?php echo number_format($total_sales, 2); ?></h3>
                </div>

                <table class="sales-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Cash Received</th>
                            <th>Change Given</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr style="text-align: center; background-color: #ffffff; border-bottom: 1px solid #ccc; font-weight: 600; color: #333;">
    <td style="padding: 10px; color: #FF6B6B#;"><?php echo htmlspecialchars($sale['products']); ?></td>
    <td style="padding: 10px;"><?php echo $sale['total_quantity']; ?></td>
    <td style="padding: 10px; color: #2D3436;">₱<?php echo number_format($sale['unit_price'], 2); ?></td>
    <td style="padding: 10px; color: #2D3436;">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
    <td style="padding: 10px; color: #2D3436;">₱<?php echo number_format($sale['cash_amount'], 2); ?></td>
    <td style="padding: 10px; color: #2D3436;">₱<?php echo number_format($sale['change_amount'], 2); ?></td>
    <td style="padding: 10px; font-style: italic;"><?php echo date('M j, Y h:i A', strtotime($sale['sale_date'])); ?></td>
</tr>


                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="action-buttons">
                    <a href="new_sale.php" class="btn btn-primary"><i class="fa fa-plus"></i> New Sale</a>
                    <a href="../dashboard.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
