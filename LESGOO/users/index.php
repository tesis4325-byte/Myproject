<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

$users = $conn->query("SELECT * FROM users ORDER BY username");
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];
$total_librarians = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='librarian'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - NORSU Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #6c757d;
        }
        .role-badge {
            transition: all 0.3s ease;
        }
        .role-badge:hover {
            transform: scale(1.1);
        }
        .stats-card {
            border-radius: 15px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            overflow: hidden;
            position: relative;
        }
        .stats-card .icon {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 5rem;
            opacity: 0.2;
            transform: rotate(-15deg);
            transition: all 0.3s ease;
        }
        .stats-card:hover .icon {
            transform: rotate(0) scale(1.1);
            opacity: 0.3;
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
         
            <a href="../borrowings/index.php" class="sidebar-link">
                <i class="fas fa-exchange-alt"></i> Borrowings
            </a>
            <a href="../reports/index.php" class="sidebar-link">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="../users/index.php" class="sidebar-link active">
                <i class="fas fa-user-shield"></i> Users
            </a>
            <a href="../auth/logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Users Management</h2>
                <nav aria-label="breadcrumb">
                  
                </nav>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus-circle me-2"></i> Add New User
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card mb-4">
                    <div class="card-body">
                        <h3 class="mb-0"><?php echo $total_users; ?></h3>
                        <p class="mb-0">Total Users</p>
                        <i class="fas fa-users icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card mb-4">
                    <div class="card-body">
                        <h3 class="mb-0"><?php echo $total_admins; ?></h3>
                        <p class="mb-0">Administrators</p>
                        <i class="fas fa-user-shield icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card mb-4">
                    <div class="card-body">
                        <h3 class="mb-0"><?php echo $total_librarians; ?></h3>
                        <p class="mb-0">Librarians</p>
                        <i class="fas fa-user-tie icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card fade-in">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="badge role-badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'info'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if($user['username'] != 'admin'): ?>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary edit-user" 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-user" 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addUserForm" action="add_user.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                            <div class="invalid-feedback">Please enter username</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                            <div class="invalid-feedback">Please enter password</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                            <div class="invalid-feedback">Please enter full name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="librarian">Librarian</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div class="invalid-feedback">Please select a role</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editUserForm" action="edit_user.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                            <div class="invalid-feedback">Please enter username</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                            <div class="invalid-feedback">Please enter full name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="librarian">Librarian</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div class="invalid-feedback">Please select a role</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control">
                            <div class="form-text">Only fill this if you want to change the password</div>
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
            const table = initDataTable('#usersTable');

            // Edit user
            $('.edit-user').click(function() {
                const id = $(this).data('id');
                $.getJSON('get_user.php?id=' + id, function(data) {
                    $('#edit_id').val(data.id);
                    $('#edit_username').val(data.username);
                    $('#edit_full_name').val(data.full_name);
                    $('#edit_role').val(data.role);
                    $('#editUserModal').modal('show');
                });
            });

            // Delete user
            $('.delete-user').click(function() {
                const id = $(this).data('id');
                confirmAction('Are you sure you want to delete this user?', function() {
                    window.location.href = 'delete_user.php?id=' + id;
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