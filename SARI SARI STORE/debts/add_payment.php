<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

// Initialize variables
$debt_id = $payment_amount = '';
$errors = [];

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate debt ID
    if(empty(trim($_POST["debt_id"]))) {
        $errors[] = "Please select a debt.";
    } else {
        $debt_id = trim($_POST["debt_id"]);
    }
    
    // Validate payment amount
    if(empty(trim($_POST["payment_amount"])) || !is_numeric(trim($_POST["payment_amount"]))) {
        $errors[] = "Please enter a valid payment amount.";
    } else {
        $payment_amount = trim($_POST["payment_amount"]);
    }
    
    // Process payment if no errors
    if(empty($errors)) {
        // Start transaction
        $mysqli->begin_transaction();
        
        try {
            // 1. Insert payment record
            $sql = "INSERT INTO debt_payments (debt_id, amount, date_paid) VALUES (?, ?, NOW())";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("id", $debt_id, $payment_amount);
            $stmt->execute();
            $stmt->close();
            
            // 2. Update debt balance
            $sql = "UPDATE debts SET balance = balance - ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("di", $payment_amount, $debt_id);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $mysqli->commit();
            
            header("Location: list.php");
            exit;
        } catch (Exception $e) {
            // Rollback on error
            $mysqli->rollback();
            $errors[] = "Payment processing failed: " . $e->getMessage() . ". Please try again.";
        }
    }
}

// Get list of active debts for dropdown
$debts = [];
$sql = "SELECT id, creditor_name, balance FROM debts WHERE balance > 0 ORDER BY creditor_name";
if($result = $mysqli->query($sql)) {
    while($row = $result->fetch_assoc()) {
        $debts[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Record Payment</title>
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
            <h1>Record Payment</h1>
            
            <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Select Debt</label>
                    <select name="debt_id" class="form-control" required>
                        <option value="">-- Select Debt --</option>
                        <?php foreach($debts as $debt): ?>
                            <option value="<?php echo $debt['id']; ?>" <?php echo ($debt_id == $debt['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($debt['creditor_name'] . ' (Balance: ' . number_format($debt['balance'], 2) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Amount</label>
                    <input type="number" step="0.01" name="payment_amount" class="form-control" value="<?php echo htmlspecialchars($payment_amount); ?>" required>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Record Payment">
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>