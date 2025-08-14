<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

// Fetch all debts
$sql = "SELECT d.*, SUM(dp.amount) AS paid_amount 
        FROM debts d 
        LEFT JOIN debt_payments dp ON d.id = dp.debt_id 
        GROUP BY d.id";
$result = $mysqli->query($sql);

// Calculate remaining balance for each debt
$debts = [];
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['remaining_balance'] = $row['total_amount'] - ($row['paid_amount'] ?? 0);
        $debts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Debts Management</title>
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
                    <li><a href="../inventory/list.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                    <li><a href="../sales/new_sale.php"><i class="fas fa-cash-register"></i> New Sale</a></li>
                    <li><a href="../expenses/add.php"><i class="fas fa-receipt"></i> Add Expense</a></li>

                    <li class="active"><a href="list.php"><i class="fas fa-file-invoice-dollar"></i> Debts</a></li>
                    <li>
                        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="container">
            <h1>Debts Management</h1>
            <a href="add.php" class="btn btn-primary">Add New Debt</a>
            
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>Creditor</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Balance</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($debts)): ?>
                        <?php foreach($debts as $debt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($debt['creditor_name']); ?></td>
                                <td><?php echo htmlspecialchars($debt['items']); ?></td>
                                <td>₱<?php echo number_format($debt['total_amount'], 2); ?></td>
                                <td>₱<?php echo number_format($debt['paid_amount'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($debt['remaining_balance'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($debt['date_created'])); ?></td>
                                <td><?php echo ucfirst($debt['status']); ?></td>
                                <td>
                                    <a href="add_payment.php?id=<?php echo $debt['id']; ?>" class="btn btn-sm">Add Payment</a>
                                    <a href="edit.php?id=<?php echo $debt['id']; ?>" class="btn btn-sm">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No debts found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>