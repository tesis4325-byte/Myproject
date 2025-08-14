<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

// Initialize variables
$creditor_name = $items = $amount = $quantity = $product_id = '';
$errors = [];

// Get all products for dropdown
$products = [];
$sql = "SELECT id, product_name, quantity FROM products ORDER BY product_name";
if($result = $mysqli->query($sql)) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate creditor name
    if(empty(trim($_POST["creditor_name"]))) {
        $errors[] = "Please enter creditor name.";
    } else {
        $creditor_name = trim($_POST["creditor_name"]);
    }
    
    // Validate product selection
    if(empty($_POST["product_id"])) {
        $errors[] = "Please select an item from inventory.";
    } else {
        $product_id = $_POST["product_id"];
        // Get product details
        $product_sql = "SELECT product_name, quantity FROM products WHERE id = ?";
        if($stmt = $mysqli->prepare($product_sql)) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $items = $product['product_name'];
            $stmt->close();
        }
    }
    
    // Validate quantity
    if(empty(trim($_POST["quantity"])) || !is_numeric(trim($_POST["quantity"])) || trim($_POST["quantity"]) <= 0) {
        $errors[] = "Please enter a valid quantity.";
    } else {
        $quantity = trim($_POST["quantity"]);
        // Check if enough stock exists
        if($quantity > $product['quantity']) {
            $errors[] = "Not enough stock available. Only " . $product['quantity'] . " items left.";
        }
    }
    
    // Validate amount
    if(empty(trim($_POST["amount"])) || !is_numeric(trim($_POST["amount"]))) {
        $errors[] = "Please enter a valid amount.";
    } else {
        $amount = trim($_POST["amount"]);
    }
    
    // Insert if no errors
    if(empty($errors)) {
        // Start transaction
        $mysqli->begin_transaction();
        
        try {
            // Insert debt record
            $sql = "INSERT INTO debts (creditor_name, items, total_amount, balance, date_created) VALUES (?, ?, ?, ?, NOW())";
            if($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("ssdd", $creditor_name, $items, $amount, $amount);
                $stmt->execute();
                $stmt->close();
            }
            
            // Update inventory
            $update_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
            if($stmt = $mysqli->prepare($update_sql)) {
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Commit transaction
            $mysqli->commit();
            header("Location: list.php");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $mysqli->rollback();
            $errors[] = "Transaction failed: " . $e->getMessage();
        }
    }
    
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add New Debt</title>
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
            <h1>Add New Debt</h1>
            
            <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Creditor Name</label>
                    <input type="text" name="creditor_name" class="form-control" value="<?php echo htmlspecialchars($creditor_name); ?>">
                </div>
                <div class="form-group">
                    <label>Select Item from Inventory</label>
                    <select name="product_id" class="form-control" required>
                        <option value="">-- Select Item --</option>
                        <?php foreach($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" <?php echo $product_id == $product['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['product_name']); ?> (<?php echo $product['quantity']; ?> available)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="1" value="<?php echo htmlspecialchars($quantity); ?>" required>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($amount); ?>">
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Save">
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
                <input type="hidden" name="date_created" value="<?php echo date('Y-m-d H:i:s'); ?>">
            </form>
        </div>
    </div>
</body>
</html>