<?php
include 'includes/header.php';
require_once '../config/database.php';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$total_logs = $conn->query("SELECT COUNT(*) as count FROM audit_logs")->fetch_assoc()['count'];
$total_pages = ceil($total_logs / $per_page);

// Get audit logs with user details
$logs = $conn->query("
    SELECT al.*, CONCAT(u.firstname, ' ', u.lastname) as user_fullname, u.email
    FROM audit_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT $offset, $per_page
");

// Filter handling
$filters = [];
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $filters[] = "al.user_id = " . (int)$_GET['user_id'];
}
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters[] = "DATE(al.created_at) >= '" . $conn->real_escape_string($_GET['date_from']) . "'";
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters[] = "DATE(al.created_at) <= '" . $conn->real_escape_string($_GET['date_to']) . "'";
}

// Build query with filters
$where_clause = '';
if (!empty($filters)) {
    $where_clause = "WHERE " . implode(" AND ", $filters);
}

?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Audit Logs</h2>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="date_from" 
                           value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="date_to"
                           value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="audit-logs.php" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if ($log['user_fullname']): ?>
                                        <div><?php echo htmlspecialchars($log['user_fullname']); ?></div>                        <small class="text-muted"><?php echo htmlspecialchars($log['email'] ?? ''); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>                                <td>
                                    <div>
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </div>
                                </td>                                <td><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></td>                                <td>
                                    <?php if (isset($log['additional_details']) && !empty($log['additional_details'])): ?>
                                        <button type="button" class="btn btn-sm btn-info" 
                                                data-bs-toggle="popover" 
                                                data-bs-content="<?php echo htmlspecialchars($log['additional_details'] ?? ''); ?>">
                                            <i class="fas fa-info-circle"></i> View Details
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Audit log pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>" <?php echo $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>" <?php echo $page >= $total_pages ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            trigger: 'click',
            placement: 'left'
        });
    });

    // Hide popovers when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.hasAttribute('data-bs-toggle')) {
            popoverTriggerList.forEach(function(popover) {
                const instance = bootstrap.Popover.getInstance(popover);
                if (instance) {
                    instance.hide();
                }
            });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
