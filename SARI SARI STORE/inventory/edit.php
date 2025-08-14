<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: list.php");
    exit;
}

$id = $_GET['id'];
$product_name = $barcode = $price = $quantity = $supplier_cost = '';
$errors = [];

$sql = "SELECT * FROM products WHERE id = ?";
if($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $product_name = $row['product_name'];
            $barcode = $row['barcode'];
            $price = $row['price'];
            $quantity = $row['quantity'];
            $supplier_cost = $row['supplier_cost'];
        } else {
            header("Location: list.php");
            exit;
        }
    } else {
        $errors[] = "Oops! Something went wrong. Please try again later.";
    }
    
    $stmt->close();
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(empty(trim($_POST["product_name"]))) {
        $errors[] = "Please enter product name.";
    } else {
        $product_name = trim($_POST["product_name"]);
    }

    $barcode = trim($_POST["barcode"]);

    if(empty(trim($_POST["price"])) || !is_numeric(trim($_POST["price"]))) {
        $errors[] = "Please enter a valid price.";
    } else {
        $price = trim($_POST["price"]);
    }

    if(empty(trim($_POST["quantity"])) || !is_numeric(trim($_POST["quantity"]))) {
        $errors[] = "Please enter a valid quantity.";
    } else {
        $quantity = trim($_POST["quantity"]);
    }

    if(empty(trim($_POST["supplier_cost"])) || !is_numeric(trim($_POST["supplier_cost"]))) {
        $errors[] = "Please enter a valid supplier cost.";
    } else {
        $supplier_cost = trim($_POST["supplier_cost"]);
    }

    if(empty($errors)) {
        $sql = "UPDATE products SET product_name = ?, barcode = ?, price = ?, quantity = ?, supplier_cost = ? WHERE id = ?";
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssddsi", $product_name, $barcode, $price, $quantity, $supplier_cost, $id);
            if($stmt->execute()) {
                header("Location: list.php");
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
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B6B;
            --secondary: #FF8E53;
            --accent: #FFD166;
            --dark: #2D3436;
            --light: #F7F1E5;
            --success: #06D6A0;
            --warning: #EF476F;
            --info: #118AB2;
            --white: #ffffff;
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: var(--white);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            animation: fadeIn 0.6s ease-in-out;
        }

        h1 {
            text-align: center;
            color: var(--dark);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            transition: border 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
            box-shadow: var(--shadow-hover);
        }

        .btn-secondary {
            background-color: #ccc;
            color: var(--dark);
            margin-left: 10px;
        }

        .alert {
            background-color: var(--warning);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
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
    <h1>Edit Product</h1>

    <?php if(!empty($errors)): ?>
      <div class="alert">
        <?php foreach($errors as $error): ?>
          <p><?php echo $error; ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id; ?>" method="post">
      <div class="form-group">
        <label>Product Name</label>
        <input type="text" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>">
      </div>
      <div class="form-group">
        <label>Barcode (optional)</label>
        <input type="text" name="barcode" value="<?php echo htmlspecialchars($barcode); ?>">
      </div>
      <div class="form-group">
        <label>Price</label>
        <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($price); ?>">
      </div>
      <div class="form-group">
        <label>Quantity</label>
        <input type="number" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
      </div>
      <div class="form-group">
        <label>Supplier Cost</label>
        <input type="number" step="0.01" name="supplier_cost" value="<?php echo htmlspecialchars($supplier_cost); ?>">
      </div>
      <div class="form-group">
        <input type="submit" class="btn" value="Save">
        <a href="list.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
