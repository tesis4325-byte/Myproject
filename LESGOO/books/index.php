<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

$books = $conn->query("SELECT * FROM books ORDER BY title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Management - NORSU Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
            <a href="../books/index.php" class="sidebar-link active">
                <i class="fas fa-book"></i> Books
            </a>
            <a href="../members/index.php" class="sidebar-link">
                <i class="fas fa-users"></i> Members
            </a>
            
            
            <a href="../borrowings/index.php" class="sidebar-link">
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
                <h2 class="mb-1">Books Management</h2>
                <nav aria-label="breadcrumb">
                    
                </nav>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus-circle me-2"></i> Add New Book
            </button>
        </div>

        <div class="card fade-in">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="booksTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>ISBN</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($book = $books->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td>
                                    <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($book['title']); ?>">
                                        <?php echo strlen($book['title']) > 30 ? substr(htmlspecialchars($book['title']), 0, 30) . '...' : htmlspecialchars($book['title']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($book['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo $book['available']; ?>/<?php echo $book['quantity']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $book['status'] == 'available' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($book['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary edit-book" 
                                                data-id="<?php echo $book['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Edit Book">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-book" 
                                                data-id="<?php echo $book['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Delete Book">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Book
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addBookForm" action="add_book.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" class="form-control" required>
                            <div class="invalid-feedback">Please enter ISBN</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                            <div class="invalid-feedback">Please enter title</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Author</label>
                            <input type="text" name="author" class="form-control" required>
                            <div class="invalid-feedback">Please enter author</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" required>
                            <div class="invalid-feedback">Please enter category</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" required min="1" value="1">
                            <div class="invalid-feedback">Please enter quantity</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Book
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editBookForm" action="edit_book.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" id="edit_isbn" class="form-control" required>
                            <div class="invalid-feedback">Please enter ISBN</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                            <div class="invalid-feedback">Please enter title</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Author</label>
                            <input type="text" name="author" id="edit_author" class="form-control" required>
                            <div class="invalid-feedback">Please enter author</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" id="edit_category" class="form-control" required>
                            <div class="invalid-feedback">Please enter category</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" required min="1">
                            <div class="invalid-feedback">Please enter quantity</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="edit_location" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
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
            const table = initDataTable('#booksTable');

            // Edit book
            $('.edit-book').click(function() {
                const id = $(this).data('id');
                $.getJSON('get_book.php?id=' + id, function(data) {
                    $('#edit_id').val(data.id);
                    $('#edit_isbn').val(data.isbn);
                    $('#edit_title').val(data.title);
                    $('#edit_author').val(data.author);
                    $('#edit_category').val(data.category);
                    $('#edit_quantity').val(data.quantity);
                    $('#edit_location').val(data.location);
                    $('#editBookModal').modal('show');
                });
            });

            // Delete book
            $('.delete-book').click(function() {
                const id = $(this).data('id');
                confirmAction('Are you sure you want to delete this book?', function() {
                    window.location.href = 'delete_book.php?id=' + id;
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