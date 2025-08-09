<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

$members = $conn->query("SELECT * FROM members ORDER BY member_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - NORSU Library System</title>
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
            <a href="../books/index.php" class="sidebar-link">
                <i class="fas fa-book"></i> Books
            </a>
            <a href="../members/index.php" class="sidebar-link active">
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
                <h2 class="mb-1">Members Management</h2>
                <nav aria-label="breadcrumb">
                    
                </nav>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="fas fa-plus-circle me-2"></i> Add New Member
            </button>
        </div>

        <div class="card fade-in">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="membersTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Member ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($member = $members->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $member['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info view-member" 
                                                data-id="<?php echo $member['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary edit-member" 
                                                data-id="<?php echo $member['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Edit Member">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-member" 
                                                data-id="<?php echo $member['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Delete Member">
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

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Member
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addMemberForm" action="add_member.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Member ID</label>
                            <input type="text" name="member_id" class="form-control" required>
                            <div class="invalid-feedback">Please enter Member ID</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                            <div class="invalid-feedback">Please enter full name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                            <div class="invalid-feedback">Please enter a valid email</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" required>
                            <div class="invalid-feedback">Please enter phone number</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Member
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editMemberForm" action="edit_member.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Member ID</label>
                            <input type="text" name="member_id" id="edit_member_id" class="form-control" required>
                            <div class="invalid-feedback">Please enter Member ID</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                            <div class="invalid-feedback">Please enter full name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                            <div class="invalid-feedback">Please enter a valid email</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                            <div class="invalid-feedback">Please enter phone number</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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

    <!-- View Member Modal -->
    <div class="modal fade" id="viewMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Member Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mb-3">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                        <h4 id="view_full_name" class="mb-1"></h4>
                        <p id="view_member_id" class="text-muted"></p>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email</label>
                            <p id="view_email" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Phone</label>
                            <p id="view_phone" class="mb-0"></p>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label text-muted">Address</label>
                            <p id="view_address" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <p id="view_status" class="mb-0"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Member Since</label>
                            <p id="view_created_at" class="mb-0"></p>
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
            const table = initDataTable('#membersTable');

            // View member
            $('.view-member').click(function() {
                const id = $(this).data('id');
                $.getJSON('get_member.php?id=' + id, function(data) {
                    $('#view_member_id').text(data.member_id);
                    $('#view_full_name').text(data.full_name);
                    $('#view_email').text(data.email);
                    $('#view_phone').text(data.phone);
                    $('#view_address').text(data.address);
                    $('#view_status').html(`<span class="badge bg-${data.status === 'active' ? 'success' : 'danger'}">${data.status}</span>`);
                    $('#view_created_at').text(new Date(data.created_at).toLocaleDateString());
                    $('#viewMemberModal').modal('show');
                });
            });

            // Edit member
            $('.edit-member').click(function() {
                const id = $(this).data('id');
                $.getJSON('get_member.php?id=' + id, function(data) {
                    $('#edit_id').val(data.id);
                    $('#edit_member_id').val(data.member_id);
                    $('#edit_full_name').val(data.full_name);
                    $('#edit_email').val(data.email);
                    $('#edit_phone').val(data.phone);
                    $('#edit_address').val(data.address);
                    $('#edit_status').val(data.status);
                    $('#editMemberModal').modal('show');
                });
            });

            // Delete member
            $('.delete-member').click(function() {
                const id = $(this).data('id');
                confirmAction('Are you sure you want to delete this member?', function() {
                    window.location.href = 'delete_member.php?id=' + id;
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