<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();

// Initialize variables
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$total = 0;

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add item to cart
    if(isset($_POST['add_item'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        // Get product details
        $sql = "SELECT id, product_name, price FROM products WHERE id = ?";
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $product_id);
            
            if($stmt->execute()) {
                $result = $stmt->get_result();
                
                if($result->num_rows == 1) {
                    $product = $result->fetch_assoc();
                    
                    // Add to cart or update quantity
                    if(isset($cart[$product_id])) {
                        $cart[$product_id]['quantity'] += $quantity;
                    } else {
                        $cart[$product_id] = [
                            'id' => $product['id'],
                            'name' => $product['product_name'],
                            'price' => $product['price'],
                            'quantity' => $quantity
                        ];
                    }
                }
            }
            $stmt->close();
        }
    }
    
    // Remove item from cart
    if(isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        if(isset($cart[$product_id])) {
            unset($cart[$product_id]);
        }
    }
    
    // Process checkout
    if(isset($_POST['checkout'])) {
        // Check inventory levels first
        $out_of_stock = false;
        foreach($cart as $item) {
            $sql = "SELECT quantity FROM products WHERE id = ?";
            if($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    if($row['quantity'] < $item['quantity']) {
                        $out_of_stock = true;
                        $errors[] = "\"".htmlspecialchars($item['name'])."\" is out of stock or insufficient quantity available.";
                    }
                }
                $stmt->close();
            }
        }
        
        if(!$out_of_stock) {
            // Start transaction
            $mysqli->begin_transaction();
            
            try {
                // Record sale for each item
                foreach($cart as $item) {
                    $sql = "INSERT INTO sales (product_id, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?)";
                    $stmt = $mysqli->prepare($sql);
                    $total_amount = $item['price'] * $item['quantity'];
                    $stmt->bind_param("iidd", $item['id'], $item['quantity'], $item['price'], $total_amount);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Update inventory
                    $sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("ii", $item['quantity'], $item['id']);
                    $stmt->execute();
                    $stmt->close();
                }
            
            // Commit transaction
            $mysqli->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            $cart = [];
            
            // Redirect to success page
            header("Location: summary.php");
            exit;
            } catch (Exception $e) {
                // Rollback transaction on error
                $mysqli->rollback();
                $errors[] = "Error processing sale: " . $e->getMessage();
            }
        }
    }
    
    // Update session cart
    $_SESSION['cart'] = $cart;
}

// Calculate total
foreach($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Get all products for dropdown
$products = [];
$sql = "SELECT id, product_name, price FROM products ORDER BY product_name";
if($result = $mysqli->query($sql)) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>New Sales</title>
    <link rel="stylesheet" href="../assets/css/newsales.css" />
</head>
<body>
    <div class="dashboard-wrapper">

        <!-- Sidebar: copy from your dashboard sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa fa-store"></i> <!-- or your logo icon -->
                <span>Store Manager</span>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="../dashboard.php">Dashboard</a></li>
                    <li class="active"><a href="new-sale.php">New Sale</a></li>
                    <li><a href="inventory-list.php">Inventory</a></li>
                    <li><a href="add-expense.php">Add Expense</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="../logout.php" class="logout-btn">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="main-content">
            <div class="pos-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
                <h1 style="text-align: left; margin-bottom: 30px;">New Sale</h1>

                <div class="pos-layout" style="display: flex; gap: 20px;">
                    <div class="product-selection" style="flex: 1; background: var(--white); padding: 20px; border-radius: 8px; box-shadow: var(--shadow);">
                        <a href="../dashboard.php" class="btn" style="margin-bottom: 20px;">Return to Dashboard</a>
                        <h2 style="margin-top: 0;">Add Product</h2>
                        <form method="post">
                            <div class="form-group">
                                <label>Product</label>
                                <select name="product_id" class="form-control" required>
                                    <option value="">Select Product</option>
                                    <?php foreach($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?> - ₱<?php echo number_format($product['price'], 2); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                            </div>
                            <button type="submit" name="add_item" class="btn btn-primary">Add to Cart</button>
                        </form>
                    </div>

                    <div class="cart-display" style="flex: 1; background: var(--white); padding: 20px; border-radius: 8px; box-shadow: var(--shadow);">
                        <h2 style="margin-top: 0;">Shopping Cart</h2>

                        <?php if(empty($cart)): ?>
                            <p>Your cart is empty</p>
                        <?php else: ?>
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($cart as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        <td>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="remove_item" class="btn btn-danger">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3"><strong>Total</strong></td>
                                        <td colspan="2">₱<?php echo number_format($total, 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <form method="post">
                                <button type="submit" name="checkout" class="btn btn-success">Checkout</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

    </div>
</body>
</html>
