<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

// Initialize variables
$description = $amount = $category = '';
$errors = [];

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate description
    if(empty(trim($_POST["description"]))) {
        $errors[] = "Please enter expense description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Validate amount
    if(empty(trim($_POST["amount"])) || !is_numeric(trim($_POST["amount"]))) {
        $errors[] = "Please enter a valid amount.";
    } else {
        $amount = trim($_POST["amount"]);
    }
    
    // Validate category
    if(empty(trim($_POST["category"]))) {
        $errors[] = "Please select a category.";
    } else {
        $category = trim($_POST["category"]);
    }
    
    // Insert if no errors
    if(empty($errors)) {
        $sql = "INSERT INTO expenses (description, amount, category) VALUES (?, ?, ?)";
        
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("sds", $description, $amount, $category);
            
            if($stmt->execute()) {
                header("Location: report.php");
                exit;
            } else {
                $errors[] = "Something went wrong. Please try again later.";
            }
            
            $stmt->close();
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
    <title>Add Expenses</title>
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
                <li><a href="http://localhost/SARI%20SARI%20STORE/debts/list.php"><i class="fas fa-file-invoice-dollar"></i> Debts</a></li>

                <li>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
      </nav>
    </aside>
    <div class="container">
        <h1>Add New Expense</h1>
        
        <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($description); ?>">
            </div>
            <div class="form-group">
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($amount); ?>">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control" required>
                    <option value="">Select Category</option>
                    <option value="Restock" <?php echo $category == 'Restock' ? 'selected' : ''; ?>>Restock</option>
                    <option value="Utilities" <?php echo $category == 'Utilities' ? 'selected' : ''; ?>>Utilities</option>
                    <option value="Maintenance" <?php echo $category == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="Other" <?php echo $category == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Save">
                <a href="report.php" class="btn btn-secondary">Cancel</a>
                 <a href="http://localhost/SARI%20SARI%20STORE/expenses/report.php" class="btn">Expenses Reports</a>
            </div>
        </form>
    </div>
</body>
</html>