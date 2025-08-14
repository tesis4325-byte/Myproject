<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

// Check if debt ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$debt_id = $mysqli->real_escape_string($_GET['id']);

// Fetch debt details
$sql = "SELECT * FROM debts WHERE id = '$debt_id'";
$result = $mysqli->query($sql);

if($result->num_rows === 0) {
    header('Location: list.php');
    exit;
}

$debt = $result->fetch_assoc();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $creditor_name = $mysqli->real_escape_string($_POST['creditor_name']);
    $items = $mysqli->real_escape_string($_POST['items']);
    $total_amount = $mysqli->real_escape_string($_POST['total_amount']);
    $status = $mysqli->real_escape_string($_POST['status']);
    
    $update_sql = "UPDATE debts SET 
                    creditor_name = '$creditor_name', 
                    items = '$items', 
                    total_amount = '$total_amount', 
                    status = '$status', 
                    date_updated = NOW() 
                    WHERE id = '$debt_id'";
    
    if($mysqli->query($update_sql)) {
        header('Location: list.php?success=1');
        exit;
    } else {
        $error = "Error updating debt: " . $mysqli->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Debt</title>
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
            <h1>Edit Debt</h1>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="creditor_name">Creditor Name</label>
                    <input type="text" id="creditor_name" name="creditor_name" value="<?php echo htmlspecialchars($debt['creditor_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="items">Items</label>
                    <textarea id="items" name="items" required><?php echo htmlspecialchars($debt['items']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="total_amount">Total Amount</label>
                    <input type="number" id="total_amount" name="total_amount" step="0.01" value="<?php echo htmlspecialchars($debt['total_amount']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="pending" <?php echo $debt['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="partial" <?php echo $debt['status'] === 'partial' ? 'selected' : ''; ?>>Partial Payment</option>
                        <option value="paid" <?php echo $debt['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Debt</button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>