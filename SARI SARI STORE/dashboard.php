<?php
session_start();

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    header("Location: index.php");
    exit;
}

require_once 'includes/db.php';

$today_sales = 0;
$sql = "SELECT SUM(total_amount) as total FROM sales WHERE DATE(sale_date) = CURDATE()";
if($result = $mysqli->query($sql)){
    if($row = $result->fetch_assoc()){
        $today_sales = $row['total'] ?? 0;
    }
    $result->free();
}

$today_expenses = 0;
$sql = "SELECT SUM(amount) as total FROM expenses";
if($result = $mysqli->query($sql)){
    if($row = $result->fetch_assoc()){
        $today_expenses = $row['total'] ?? 0;
    }
    $result->free();
}

$today_payments = 0;
$sql = "SELECT SUM(amount) as total FROM debt_payments WHERE DATE(date_paid) = CURDATE()";
if($result = $mysqli->query($sql)){
    if($row = $result->fetch_assoc()){
        $today_payments = $row['total'] ?? 0;
    }
    $result->free();
}

$today_profit = $today_sales + $today_payments;

$low_stock_items = [];
$sql = "SELECT product_name, quantity FROM products WHERE quantity < 10";
if($result = $mysqli->query($sql)){
    while($row = $result->fetch_assoc()){
        $low_stock_items[] = $row;
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
  <title>Sari-Sari Store Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/edit.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <i class="fas fa-store-alt"></i>
        <span>Sari-Sari Store</span>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="active"><a href="#"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
          <li><a href="inventory/list.php"><i class="fas fa-boxes"></i> Inventory</a></li>
          <li><a href="sales/new_sale.php"><i class="fas fa-cash-register"></i> New Sale</a></li>
          <li><a href="expenses/add.php"><i class="fas fa-receipt"></i> Add Expense</a></li>
          <li><a href="http://localhost/SARI%20SARI%20STORE/debts/list.php"><i class="fas fa-file-invoice-dollar"></i> Debts</a></li>
          <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <header class="main-header">
        <div class="greeting">
          Hi, KUPAL na <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        </div>
      </header>

      <!-- Summary Cards -->
      <section class="summary-cards">
        <div class="card">
          <h3>Today's Sales</h3>
          <p>₱<?php echo number_format($today_sales, 2); ?></p>
        </div>
        <div class="card">
          <h3>Total Expenses</h3>
          <p>₱<?php echo number_format($today_expenses, 2); ?></p>
        </div>
        <div class="card">
          <h3>Today's Profit</h3>
          <p>₱<?php echo number_format($today_sales, 2); ?></p>
        </div>
      </section>

      <!-- Sales Chart -->
      <section class="chart-section">
        <h3>Sales Trend (Last 7 Days)</h3>
        <canvas id="salesChart" width="100%" height="40"></canvas>
      </section>

      <!-- Low Stock Alerts -->
      <?php if(!empty($low_stock_items)): ?>
      <section class="alert-section">
        <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Items</h3>
        <ul>
          <?php foreach($low_stock_items as $item): ?>
            <li class="low-stock"><?php echo htmlspecialchars($item['product_name']) . ' - ' . $item['quantity'] . ' remaining'; ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>
    </main>

  </div>

  <script>
    // Sample sales data (replace with real data fetched via AJAX or PHP)
    const salesData = {
      labels: [
        <?php

          for($i=6; $i>=0; $i--) {
            echo '"' . date('M d', strtotime("-$i days")) . '",';
          }
        ?>
      ],
      datasets: [{
        label: "Sales (₱)",
        data: [
          <?php
            // You can replace this with real PHP queries per day or static sample
            echo "2500, 3200, 2800, 3500, 4200, 42, $today_sales";
          ?>
        ],
        borderColor: 'var(--primary)',
        backgroundColor: 'rgba(255, 107, 107, 0.3)',
        fill: true,
        tension: 0.3,
        pointRadius: 5,
        pointHoverRadius: 7,
      }]
    };

    const config = {
      type: 'line',
      data: salesData,
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true
          }
        },
        plugins: {
          legend: {
            labels: {
              color: getComputedStyle(document.documentElement).getPropertyValue('--dark').trim(),
              font: { size: 14, weight: '600' }
            }
          }
        }
      }
    };

    const salesChart = new Chart(
      document.getElementById('salesChart'),
      config
    );
  </script>
</body>
</html>