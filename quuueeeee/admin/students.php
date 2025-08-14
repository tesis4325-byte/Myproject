<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$total_records = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch students with pagination
$stmt = $db->prepare("
    SELECT 
        s.*,
        COUNT(q.id) as total_visits,
        MAX(q.created_at) as last_visit,
        GROUP_CONCAT(DISTINCT q.service) as services_used
    FROM students s
    LEFT JOIN queue_tickets q ON s.id = q.student_id
    GROUP BY s.id
    ORDER BY s.created_at DESC
    LIMIT :offset, :records_per_page
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $db->query("
    SELECT 
        COUNT(*) as total_students,
        COUNT(DISTINCT course) as total_courses,
        AVG(CAST(year_level AS UNSIGNED)) as avg_year_level
    FROM students
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - NORSU Queue</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="../images/norsu-logo.png" alt="NORSU Logo">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="queue.php"><i class="fas fa-list-ol"></i> Queue Management</a>
                <a href="students.php" class="active"><i class="fas fa-users"></i> Students</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <?php $page_title = "Students"; ?>
            <?php require_once 'includes/header.php'; ?>
        
           

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon students">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Students</h3>
                        <p><?php echo number_format($stats['total_students']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon courses">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Courses</h3>
                        <p><?php echo number_format($stats['total_courses']); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon year">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Avg. Year Level</h3>
                        <p><?php echo number_format($stats['avg_year_level'], 1); ?></p>
                    </div>
                </div>
            </div>

            <div class="students-management">
                <div class="students-header">
                    <div class="header-left">
                        <h2>Student Records</h2>
                        <p>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> students</p>
                    </div>
                    <div class="header-actions">
                        <div class="students-filters">
                            <select id="courseFilter" class="filter-select">
                                <option value="all">All Courses</option>
                                <?php
                                $courses = $db->query("SELECT DISTINCT course FROM students ORDER BY course")->fetchAll(PDO::FETCH_COLUMN);
                                foreach ($courses as $course) {
                                    echo "<option value='" . htmlspecialchars($course) . "'>" . htmlspecialchars($course) . "</option>";
                                }
                                ?>
                            </select>
                            <select id="yearFilter" class="filter-select">
                                <option value="all">All Years</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <button class="export-btn" onclick="exportToExcel()">
                                <i class="fas fa-file-export"></i> Export
                            </button>
                        </div>
                    </div>
                </div>

                <div class="students-list">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Visits</th>
                                <th>Last Visit</th>
                                <th>Services Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                <td>
                                    <div class="student-name">
                                        <img src="../images/avatar.png" alt="Student" class="student-avatar">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                                <td><span class="badge"><?php echo $student['total_visits']; ?></span></td>
                                <td><?php echo $student['last_visit'] ? date('M d, Y', strtotime($student['last_visit'])) : 'Never'; ?></td>
                                <td>
                                    <?php
                                    $services = explode(',', $student['services_used'] ?? '');
                                    foreach (array_slice($services, 0, 3) as $service) {
                                        echo "<span class='service-tag'>" . htmlspecialchars($service) . "</span>";
                                    }
                                    if (count($services) > 3) {
                                        echo "<span class='service-tag more'>+" . (count($services) - 3) . "</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn view" onclick="viewStudent(<?php echo $student['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn edit" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete" onclick="deleteStudent(<?php echo $student['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="pagination">
                        <?php if ($total_pages > 1): ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/xlsx@0.17.0/dist/xlsx.full.min.js"></script>
        <script>
            // Enhanced search and filter functionality
            function filterStudents() {
                const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
                const courseFilter = document.getElementById('courseFilter').value;
                const yearFilter = document.getElementById('yearFilter').value;

                document.querySelectorAll('.students-table tbody tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const course = row.children[2].textContent;
                    const year = row.children[3].textContent;

                    const matchesSearch = text.includes(searchTerm);
                    const matchesCourse = courseFilter === 'all' || course === courseFilter;
                    const matchesYear = yearFilter === 'all' || year.includes(yearFilter);

                    row.style.display = matchesSearch && matchesCourse && matchesYear ? '' : 'none';
                });
            }

            // Export to Excel functionality
            function exportToExcel() {
                const table = document.querySelector('.students-table');
                const wb = XLSX.utils.table_to_book(table, { sheet: "Students" });
                XLSX.writeFile(wb, `students_export_${new Date().toISOString().slice(0,10)}.xlsx`);
            }

            // Enhanced student management functions
            async function viewStudent(id) {
                window.location.href = `student-details.php?id=${id}`;
            }

            async function editStudent(id) {
                window.location.href = `edit-student.php?id=${id}`;
            }

            async function deleteStudent(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const response = await fetch('api/delete-student.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id })
                            });

                            const data = await response.json();
                            if (data.success) {
                                Swal.fire('Deleted!', 'Student has been deleted.', 'success')
                                    .then(() => location.reload());
                            } else {
                                throw new Error(data.message);
                            }
                        } catch (error) {
                            Swal.fire('Error!', error.message, 'error');
                        }
                    }
                });
            }

            // Event listeners
            document.getElementById('studentSearch').addEventListener('input', filterStudents);
            document.getElementById('courseFilter').addEventListener('change', filterStudents);
            document.getElementById('yearFilter').addEventListener('change', filterStudents);
        </script>
    </div>
</body>
</html>