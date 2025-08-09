<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

$borrowings = $conn->query("
    SELECT b.*, bk.title as book_title, bk.isbn, m.full_name as member_name 
    FROM borrowings b 
    JOIN books bk ON b.book_id = bk.id 
    JOIN members m ON b.member_id = m.id 
    ORDER BY b.borrow_date DESC
");

$books = $conn->query("SELECT id, title, isbn FROM books WHERE available > 0");
$members = $conn->query("SELECT id, member_id, full_name FROM members WHERE status = 'active'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowings Management - NORSU Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .status-badge {
            transition: all 0.3s ease;
        }
        .status-badge:hover {
            transform: scale(1.1);
        }
        .due-soon {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .borrowing-card {
            transition: all 0.3s ease;
        }
        .borrowing-card:hover {
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
            <p class="small mb-0">CODEEEE UNIVERSITY</p>
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
           
            <a href="../borrowings/index.php" class="sidebar-link active">
                <i class="fas fa-exchange-alt"></i> Borrowings
            </a>
            <a href="../reports/index.php" class="sidebar-link">
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
                <h2 class="mb-1">Borrowings Management</h2>
                <nav aria-label="breadcrumb">
                  
                </nav>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBorrowingModal">
                <i class="fas fa-plus-circle me-2"></i> New Borrowing
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card borrowing-card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Active Borrowings</h6>
                        <h2 class="mb-0">
                            <?php 
                            $active = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE status='borrowed'")->fetch_assoc();
                            echo $active['count'];
                            ?>
                        </h2>
                        <i class="fas fa-book-reader icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card borrowing-card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Due Today</h6>
                        <h2 class="mb-0">
                            <?php 
                            $due = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE due_date = CURRENT_DATE AND status='borrowed'")->fetch_assoc();
                            echo $due['count'];
                            ?>
                        </h2>
                        <i class="fas fa-clock icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card borrowing-card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Overdue</h6>
                        <h2 class="mb-0">
                            <?php 
                            $overdue = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE status='overdue'")->fetch_assoc();
                            echo $overdue['count'];
                            ?>
                        </h2>
                        <i class="fas fa-exclamation-circle icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card borrowing-card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Returned Today</h6>
                        <h2 class="mb-0">
                            <?php 
                            $returned = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE return_date = CURRENT_DATE")->fetch_assoc();
                            echo $returned['count'];
                            ?>
                        </h2>
                        <i class="fas fa-check-circle icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card fade-in">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="borrowingsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Member</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($borrowing = $borrowings->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-book text-primary me-2"></i>
                                        <div>
                                            <div><?php echo htmlspecialchars($borrowing['book_title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($borrowing['isbn']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-secondary me-2"></i>
                                        <?php echo htmlspecialchars($borrowing['member_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?></td>
                                <td>
                                    <?php 
                                    $due_date = strtotime($borrowing['due_date']);
                                    $today = strtotime('today');
                                    $is_due_soon = ($due_date - $today) <= 2*24*60*60; // 2 days
                                    ?>
                                    <span class="<?php echo $is_due_soon && $borrowing['status'] == 'borrowed' ? 'due-soon' : ''; ?>">
                                        <?php echo date('M d, Y', $due_date); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge status-badge bg-<?php 
                                        echo $borrowing['status'] == 'borrowed' ? 'info' : 
                                            ($borrowing['status'] == 'returned' ? 'success' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($borrowing['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info view-borrowing" 
                                                data-id="<?php echo $borrowing['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if($borrowing['status'] == 'borrowed'): ?>
                                        <button class="btn btn-sm btn-success return-book" 
                                                data-id="<?php echo $borrowing['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Return Book">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if($_SESSION['role'] == 'admin'): ?>
                                        <button class="btn btn-sm btn-danger delete-borrowing" 
                                                data-id="<?php echo $borrowing['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Delete Record">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Borrowing Modal -->
    <div class="modal fade" id="addBorrowingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>New Borrowing
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addBorrowingForm" action="add_borrowing.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Book</label>
                            <select name="book_id" class="form-select" required>
                                <option value="">Select Book</option>
                                <?php while($book = $books->fetch_assoc()): ?>
                                <option value="<?php echo $book['id']; ?>">
                                    <?php echo htmlspecialchars($book['title']); ?> (<?php echo $book['isbn']; ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a book</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">Select Member</option>
                                <?php while($member = $members->fetch_assoc()): ?>
                                <option value="<?php echo $member['id']; ?>">
                                    <?php echo htmlspecialchars($member['full_name']); ?> (<?php echo $member['member_id']; ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a member</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" required 
                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            <div class="invalid-feedback">Please select a due date</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Borrowing
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Borrowing Modal -->
    <div class="modal fade" id="viewBorrowingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Borrowing Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Book Title</label>
                            <p id="view_book_title" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">ISBN</label>
                            <p id="view_isbn" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Member Name</label>
                            <p id="view_member_name" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <p id="view_status" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Borrow Date</label>
                            <p id="view_borrow_date" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Due Date</label>
                            <p id="view_due_date" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Return Date</label>
                            <p id="view_return_date" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Fine Amount</label>
                            <p id="view_fine" class="mb-0"></p>
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
    <script src="../assets/js/script.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = initDataTable('#borrowingsTable', {
                order: [[2, 'desc']]
            });

            // View borrowing details
            $('.view-borrowing').click(function() {
                const id = $(this).data('id');
                $.getJSON('get_borrowing.php?id=' + id, function(data) {
                    $('#view_book_title').text(data.book_title);
                    $('#view_isbn').text(data.isbn);
                    $('#view_member_name').text(data.member_name);
                    $('#view_status').html(`<span class="badge bg-${
                        data.status === 'borrowed' ? 'info' : 
                        (data.status === 'returned' ? 'success' : 'danger')
                    }">${data.status}</span>`);
                    $('#view_borrow_date').text(new Date(data.borrow_date).toLocaleDateString());
                    $('#view_due_date').text(new Date(data.due_date).toLocaleDateString());
                    $('#view_return_date').text(data.return_date ? new Date(data.return_date).toLocaleDateString() : '-');
                    $('#view_fine').text(data.fine > 0 ? `₱${data.fine}` : '-');
                    $('#viewBorrowingModal').modal('show');
                });
            });

            // Return book
            $('.return-book').click(function() {
                const id = $(this).data('id');
                confirmAction('Are you sure you want to mark this book as returned?', function() {
                    window.location.href = 'return_book.php?id=' + id;
                });
            });

            // Delete borrowing
            $('.delete-borrowing').click(function() {
                const id = $(this).data('id');
                confirmAction('Are you sure you want to delete this borrowing record?', function() {
                    window.location.href = 'delete_borrowing.php?id=' + id;
                });
            });

            // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });
    </script>
</body>
</html>