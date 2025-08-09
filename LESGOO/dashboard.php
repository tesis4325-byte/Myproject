<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/database.php';

// Get dashboard statistics
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_members = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$active_borrowings = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE status='borrowed'")->fetch_assoc()['count'];
$overdue_books = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE status='borrowed' AND due_date < CURRENT_DATE")->fetch_assoc()['count'];

// Get recent activities
$recent_activities = $conn->query("
    SELECT b.*, bk.title as book_title, m.full_name as member_name 
    FROM borrowings b 
    JOIN books bk ON b.book_id = bk.id 
    JOIN members m ON b.member_id = m.id 
    ORDER BY b.id DESC LIMIT 5
");

// Get popular books
$popular_books = $conn->query("
    SELECT b.title, COUNT(*) as borrow_count 
    FROM borrowings br 
    JOIN books b ON br.book_id = b.id 
    GROUP BY b.id 
    ORDER BY borrow_count DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NORSU Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .quick-action {
            transition: all 0.3s ease;
        }
        .quick-action:hover {
            transform: translateY(-5px);
        }
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
        }
        .welcome-card {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .welcome-card::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            right: -100px;
            top: -100px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <img src="assets/img/norsu-logo.png" alt="NORSU Logo" style="width: 80px;" class="mb-3">
            <h5 class="mb-1">Library System</h5>
            <p class="small mb-0">CODEEEE UNIVERSITY</p>
        </div>
        <nav>
            <a href="dashboard.php" class="sidebar-link active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="books/index.php" class="sidebar-link">
                <i class="fas fa-book"></i> Books
            </a>
            <a href="members/index.php" class="sidebar-link">
                <i class="fas fa-users"></i> Members
            </a>
           
            <a href="borrowings/index.php" class="sidebar-link">
                <i class="fas fa-exchange-alt"></i> Borrowings
            </a>
            <a href="reports/index.php" class="sidebar-link">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="users/index.php" class="sidebar-link">
                <i class="fas fa-user-shield"></i> Users
            </a>
            <?php endif; ?>
            <a href="auth/logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="welcome-card p-4 mb-4 fade-in">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                    <p class="mb-0">Here's what's happening in your library today</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo date('l, F j, Y'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-primary text-white h-100">
                    <div class="card-body">
                        <h3 class="mb-0"><?php echo $total_books; ?></h3>
                        <p class="mb-0">Total Books</p>
                        <i class="fas fa-book icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-success text-white h-100">
                    <div class="card-body">
                        <h3 class="mb-0"><?php echo $total_students; ?></h3>
                        <p class="mb-0">Total Students</p>
                        <i class="fas fa-user-graduate icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-info text-white h-100">
                    <div class="card-body">
                        <h3 class="mb-0"><?php echo $active_borrowings; ?></h3>
                        <p class="mb-0">Active Borrowings</p>
                        <i class="fas fa-book-reader icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-danger text-white h-100">
                    <div class="card-body">
                        <h3 class="mb-0"><?php echo $overdue_books; ?></h3>
                        <p class="mb-0">Overdue Books</p>
                        <i class="fas fa-exclamation-circle icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>Recent Activities
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <?php while($activity = $recent_activities->fetch_assoc()): ?>
                            <div class="timeline-item">
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($activity['member_name']); ?></strong>
                                    <?php echo $activity['status'] == 'borrowed' ? 'borrowed' : 'returned'; ?>
                                    <strong><?php echo htmlspecialchars($activity['book_title']); ?></strong>
                                </p>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($activity['borrow_date'])); ?>
                                </small>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2"></i>Popular Books
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
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
        </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>