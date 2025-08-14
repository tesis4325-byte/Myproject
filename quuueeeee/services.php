<?php
session_start();
if (!isset($_SESSION['student_id']) && !isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get current queue statistics
$stmt = $db->prepare("SELECT service, COUNT(*) as count FROM queue_tickets WHERE DATE(created_at) = CURDATE() AND status = 'waiting' GROUP BY service");
$stmt->execute();
$queueCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NORSU Registrar Services Portal</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body class="services-body">
    <!-- Header Section -->
    <header class="services-header">
        <nav class="services-nav">
            <div class="nav-left">
                <img src="images/norsu-logo.png" alt="NORSU Logo" class="nav-logo">
                <div class="nav-text">
                    <span class="nav-title">NORSU Registrar</span>
                    <span class="nav-subtitle">Mabinay Campus</span>
                </div>
            </div>
            <div class="nav-center">
                <div class="queue-stats">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span>Current Queue: <?php echo array_sum($queueCounts); ?></span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-clock"></i>
                        <span id="currentTime"></span>
                    </div>
                </div>
            </div>
            <div class="nav-right">
                <div class="user-profile">
                    <img src="images/avatar.png" alt="User" class="user-avatar">
                    <div class="user-info">
                        <span class="user-name">
                            <?php echo isset($_SESSION['student_name']) ? htmlspecialchars($_SESSION['student_name']) : "Guest User"; ?>
                        </span>
                        <span class="user-type">
                            <?php echo isset($_SESSION['student_id']) ? "Student" : "Guest"; ?>
                        </span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </header>

    <main class="services-main">
        <!-- Welcome Banner -->
        <section class="welcome-banner" data-aos="fade-down">
            <div class="banner-content">
                <h1>Welcome to NORSU Registrar Services</h1>
                <p>Select from our comprehensive range of academic services</p>
            </div>
        </section>

        <!-- Service Categories -->
        <section class="service-categories" data-aos="fade-up">
            <div class="category-tabs">
                <button class="category-tab active" data-category="all">
                    <i class="fas fa-th-large"></i>
                    <span>All Services</span>
                </button>
                <button class="category-tab" data-category="records">
                    <i class="fas fa-folder"></i>
                    <span>Records</span>
                </button>
                <button class="category-tab" data-category="enrollment">
                    <i class="fas fa-user-graduate"></i>
                    <span>Enrollment</span>
                </button>
                <button class="category-tab" data-category="documents">
                    <i class="fas fa-file-alt"></i>
                    <span>Documents</span>
                </button>
                <button class="category-tab" data-category="graduation">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Graduation</span>
                </button>
            </div>
        </section>

        <!-- Services Grid -->
        <section class="services-grid">
            <!-- Records Management Services -->
            <div class="service-card" data-category="records" data-aos="fade-up">
                <div class="service-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="service-content">
                    <h3>Transcript of Records</h3>
                    <p>Request your complete academic records</p>
                    <div class="service-meta">
                        <span><i class="fas fa-clock"></i> Processing: 3-5 days</span>
                        <span><i class="fas fa-users"></i> In Queue: <?php echo $queueCounts['TOR'] ?? 0; ?></span>
                    </div>
                    <button class="queue-btn" data-service="TOR">Get Queue Number</button>
                </div>
            </div>

            <div class="service-card" data-category="records" data-aos="fade-up">
                <div class="service-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="service-content">
                    <h3>Grade Certification</h3>
                    <p>Request official grade certifications</p>
                    <div class="service-meta">
                        <span><i class="fas fa-clock"></i> Processing: 2-3 days</span>
                        <span><i class="fas fa-users"></i> In Queue: <?php echo $queueCounts['GRADES'] ?? 0; ?></span>
                    </div>
                    <button class="queue-btn" data-service="GRADES">Get Queue Number</button>
                </div>
            </div>

            <!-- Enrollment Services -->
            <div class="service-card" data-category="enrollment" data-aos="fade-up">
                <div class="service-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="service-content">
                    <h3>Enrollment Assistance</h3>
                    <p>Get help with enrollment procedures</p>
                    <div class="service-meta">
                        <span><i class="fas fa-clock"></i> Processing: 1-2 hours</span>
                        <span><i class="fas fa-users"></i> In Queue: <?php echo $queueCounts['ENROLLMENT'] ?? 0; ?></span>
                    </div>
                    <button class="queue-btn" data-service="ENROLLMENT">Get Queue Number</button>
                </div>
            </div>

            <!-- Documents Services -->
            <div class="service-card" data-category="documents" data-aos="fade-up">
                <div class="service-icon">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div class="service-content">
                    <h3>Certificate of Registration</h3>
                    <p>Request your enrollment certification</p>
                    <div class="service-meta">
                        <span><i class="fas fa-clock"></i> Processing: 1 day</span>
                        <span><i class="fas fa-users"></i> In Queue: <?php echo $queueCounts['COR'] ?? 0; ?></span>
                    </div>
                    <button class="queue-btn" data-service="COR">Get Queue Number</button>
                </div>
            </div>

            <!-- Graduation Services -->
            <div class="service-card" data-category="graduation" data-aos="fade-up">
                <div class="service-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="service-content">
                    <h3>Graduation Requirements</h3>
                    <p>Process graduation clearance and requirements</p>
                    <div class="service-meta">
                        <span><i class="fas fa-clock"></i> Processing: 3-5 days</span>
                        <span><i class="fas fa-users"></i> In Queue: <?php echo $queueCounts['GRADUATION'] ?? 0; ?></span>
                    </div>
                    <button class="queue-btn" data-service="GRADUATION">Get Queue Number</button>
                </div>
            </div>
        </section>

        <!-- Queue Modal -->
        <!-- Queue Modal -->
        <div id="queueModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Your Queue Number</h2>
                <div class="queue-number"></div>
                <div class="queue-info"></div>
                <button class="print-btn">
                    <i class="fas fa-print"></i> Print Ticket
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="services-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> NORSU Registrar's Office - Mabinay Campus</p>
            <div class="footer-links">
                <a href="#" onclick="showHelp()">Help</a>
                <a href="#" onclick="showContact()">Contact</a>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        // Update current time
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString();
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>
    <script src="js/services.js"></script>

    <!-- Add this section after the header -->
    <div class="my-queue-section">
        <div class="container">
            <h2>My Current Queue</h2>
            <?php
            // Fetch current user's active queue
            if (isset($_SESSION['student_id'])) {
                $stmt = $db->prepare("
                    SELECT q.queue_number, q.service, q.status, q.created_at
                    FROM queue_tickets q
                    WHERE q.student_id = ? 
                    AND DATE(q.created_at) = CURRENT_DATE
                    AND q.status IN ('waiting', 'processing', 'completed')
                    ORDER BY q.created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['student_id']]);
                $current_queue = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if ($current_queue) {
                    ?>
                    <div class="current-queue-card">
                        <h3>Ticket #<?php echo htmlspecialchars($current_queue['queue_number']); ?></h3>
                        <div class="queue-details">
                            <p><strong>Service:</strong> <?php echo htmlspecialchars($current_queue['service']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge <?php echo strtolower($current_queue['status']); ?>">
                                    <?php echo ucfirst($current_queue['status']); ?>
                                </span>
                            </p>
                            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($current_queue['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php
                } else {
                    echo '<p class="no-queue">No active queue tickets.</p>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>