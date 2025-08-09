<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

// Get report data
$popular_books = $conn->query("
    SELECT b.title, COUNT(*) as borrow_count 
    FROM borrowings br 
    JOIN books b ON br.book_id = b.id 
    GROUP BY b.id 
    ORDER BY borrow_count DESC 
    LIMIT 10
");

$monthly_stats = $conn->query("
    SELECT 
        DATE_FORMAT(borrow_date, '%Y-%m') as month,
        COUNT(*) as total_borrowings,
        SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as total_returns,
        SUM(fine) as total_fines
    FROM borrowings
    WHERE borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");

$category_stats = $conn->query("
    SELECT 
        b.category,
        COUNT(*) as book_count,
        SUM(br.id IS NOT NULL) as borrow_count
    FROM books b
    LEFT JOIN borrowings br ON b.id = br.book_id
    GROUP BY b.category
    ORDER BY borrow_count DESC
");

$overdue_books = $conn->query("
    SELECT b.title, m.full_name, br.due_date,
           DATEDIFF(CURRENT_DATE, br.due_date) as days_overdue,
           DATEDIFF(CURRENT_DATE, br.due_date) * 5 as fine
    FROM borrowings br 
    JOIN books b ON br.book_id = b.id 
    JOIN members m ON br.member_id = m.id 
    WHERE br.status = 'borrowed' AND br.due_date < CURRENT_DATE
    ORDER BY br.due_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - NORSU Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            transition: all 0.3s ease;
        }
        .chart-container:hover {
            transform: scale(1.02);
        }
        .stats-card {
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <img src="../assets/img/norsu-logo.png" alt="NORSU Logo" style="width: 80px;" class="mb-3">
            <h5 class="mb-1">Library System</h5>
            <p class="small mb-0">NORSU Mabinay</p>
        </div>
        <nav>
            <a href="../dashboard.php" class="sidebar-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="../books/index.php" class="sidebar-link">
                <i class="fas fa-book"></i> Books
            </a>
            <a href="../members/index.php" class="sidebar-link">
                <i class="fas fa-users"></i> Members
            </a>
            
            <a href="../borrowings/index.php" class="sidebar-link">
                <i class="fas fa-exchange-alt"></i> Borrowings
            </a>
            <a href="../reports/index.php" class="sidebar-link active">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="../users/index.php" class="sidebar-link">
                <i class="fas fa-user-shield"></i> Users
            </a>
            <?php endif; ?>
            <a href="../auth/logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Reports & Analytics</h2>
                <nav aria-label="breadcrumb">
                   
                </nav>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-primary" onclick="printReport()">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
                <button class="btn btn-outline-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Monthly Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyStatsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Book Categories
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card stats-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2"></i>Most Popular Books
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th class="text-end">Times Borrowed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($book = $popular_books->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td class="text-end"><?php echo $book['borrow_count']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card stats-card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Overdue Books
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="overdueTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Borrower</th>
                                        <th>Days Overdue</th>
                                        <th class="text-end">Fine</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($book = $overdue_books->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['full_name']); ?></td>
                                        <td><?php echo $book['days_overdue']; ?> days</td>
                                        <td class="text-end">₱<?php echo number_format($book['fine'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable for overdue books
            const overdueTable = initDataTable('#overdueTable');

            // Monthly Statistics Chart
            const monthlyData = <?php 
                $chartData = array();
                while($stat = $monthly_stats->fetch_assoc()) {
                    $chartData[] = array(
                        'month' => date('M Y', strtotime($stat['month'].'-01')),
                        'borrowings' => (int)$stat['total_borrowings'],
                        'returns' => (int)$stat['total_returns'],
                        'fines' => (float)$stat['total_fines']
                    );
                }
                echo json_encode($chartData);
            ?>;

            new Chart(document.getElementById('monthlyStatsChart'), {
                type: 'line',
                data: {
                    labels: monthlyData.map(row => row.month),
                    datasets: [
                        {
                            label: 'Borrowings',
                            data: monthlyData.map(row => row.borrowings),
                            borderColor: '#1e3c72',
                            tension: 0.1
                        },
                        {
                            label: 'Returns',
                            data: monthlyData.map(row => row.returns),
                            borderColor: '#28a745',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Category Statistics Chart
            const categoryData = <?php 
                $catData = array();
                while($cat = $category_stats->fetch_assoc()) {
                    $catData[] = array(
                        'category' => $cat['category'],
                        'book_count' => (int)$cat['book_count'],
                        'borrow_count' => (int)$cat['borrow_count']
                    );
                }
                echo json_encode($catData);
            ?>;

            new Chart(document.getElementById('categoryChart'), {
                type: 'doughnut',
                data: {
                    labels: categoryData.map(row => row.category),
                    datasets: [{
                        data: categoryData.map(row => row.borrow_count),
                        backgroundColor: [
                            '#1e3c72', '#2a5298', '#3498db', '#2ecc71', '#e74c3c',
                            '#f1c40f', '#9b59b6', '#34495e', '#16a085', '#d35400'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    }
                }
            });
        });

        // Print report function
        function printReport() {
            window.print();
        }

        // Export to Excel function
        function exportToExcel() {
            window.location.href = 'export_report.php';
        }
    </script>
</body>
</html>