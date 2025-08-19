<?php
/**
 * Helper Functions
 * Barangay Document Request and Tracking System
 */

require_once 'config.php';

/**
 * Generate unique request number
 */
function generateRequestNumber() {
    $prefix = 'BRG';
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $random = strtoupper(substr(md5(uniqid()), 0, 4));
    
    return $prefix . $year . $month . $day . $random;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * Format date and time for display
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    if (!$datetime) return '';
    return date($format, strtotime($datetime));
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'badge bg-warning';
        case 'processing':
            return 'badge bg-info';
        case 'approved':
            return 'badge bg-success';
        case 'rejected':
            return 'badge bg-danger';
        case 'released':
            return 'badge bg-primary';
        default:
            return 'badge bg-secondary';
    }
}

/**
 * Get status text
 */
function getStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'Pending';
        case 'processing':
            return 'Processing';
        case 'approved':
            return 'Approved';
        case 'rejected':
            return 'Rejected';
        case 'released':
            return 'Released';
        default:
            return 'Unknown';
    }
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }
    
    return true;
}

/**
 * Upload file
 */
function uploadFile($file, $destination) {
    if (!validateFileUpload($file)) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '.' . $extension;
    $filepath = $destination . $filename;
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Delete file
 */
function deleteFile($filename, $directory) {
    $filepath = $directory . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Get system setting
 */
function getSystemSetting($key, $default = '') {
    global $db;
    $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
    $result = $db->fetch($sql, [$key]);
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update system setting
 */
function updateSystemSetting($key, $value) {
    global $db;
    $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
    return $db->query($sql, [$key, $value, $value]);
}

/**
 * Get document type by ID
 */
function getDocumentType($id) {
    global $db;
    $sql = "SELECT * FROM document_types WHERE id = ? AND is_active = 1";
    return $db->fetch($sql, [$id]);
}

/**
 * Get all active document types
 */
function getAllDocumentTypes() {
    global $db;
    $sql = "SELECT * FROM document_types WHERE is_active = 1 ORDER BY name";
    return $db->fetchAll($sql);
}

/**
 * Get resident by user ID
 */
function getResidentByUserId($userId) {
    global $db;
    $sql = "SELECT r.*, u.username, u.email, u.status FROM residents r 
            JOIN users u ON r.user_id = u.id WHERE u.id = ?";
    $result = $db->fetch($sql, [$userId]);
    return $result ? $result : null;
}

/**
 * Get request by ID
 */
function getRequestById($id) {
    global $db;
    $sql = "SELECT dr.*, dt.name as document_type, dt.processing_fee,
            r.first_name, r.last_name, r.middle_name, r.address, r.barangay, r.city, r.province,
            u.username as resident_username, u.email as resident_email
            FROM document_requests dr
            JOIN document_types dt ON dr.document_type_id = dt.id
            JOIN residents r ON dr.resident_id = r.id
            JOIN users u ON r.user_id = u.id
            WHERE dr.id = ?";
    return $db->fetch($sql, [$id]);
}

/**
 * Get requests by resident ID
 */
function getRequestsByResidentId($residentId, $limit = null) {
    global $db;
    $sql = "SELECT dr.*, dt.name as document_type, dt.processing_fee,
            dr.submitted_at as created_at, dr.submitted_at as updated_at
            FROM document_requests dr
            JOIN document_types dt ON dr.document_type_id = dt.id
            WHERE dr.resident_id = ?
            ORDER BY dr.submitted_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $result = $db->fetchAll($sql, [$residentId]);
    return $result ? $result : [];
}

/**
 * Get all requests with filters
 */
function getAllRequests($filters = []) {
    global $db;
    
    $sql = "SELECT dr.*, dt.name as document_type,
            r.first_name, r.last_name, r.middle_name,
            u.username as resident_username
            FROM document_requests dr
            JOIN document_types dt ON dr.document_type_id = dt.id
            JOIN residents r ON dr.resident_id = r.id
            JOIN users u ON r.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND dr.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['document_type_id'])) {
        $sql .= " AND dr.document_type_id = ?";
        $params[] = $filters['document_type_id'];
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(dr.submitted_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(dr.submitted_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    $sql .= " ORDER BY dr.submitted_at DESC";
    
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . (int)$filters['limit'];
    }
    
    return $db->fetchAll($sql, $params);
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    global $db;
    
    $stats = [];
    
    // Total requests
    $result = $db->fetch("SELECT COUNT(*) as count FROM document_requests");
    $stats['total_requests'] = $result['count'];
    
    // Pending requests
    $result = $db->fetch("SELECT COUNT(*) as count FROM document_requests WHERE status = 'pending'");
    $stats['pending_requests'] = $result['count'];
    
    // Processing requests
    $result = $db->fetch("SELECT COUNT(*) as count FROM document_requests WHERE status = 'processing'");
    $stats['processing_requests'] = $result['count'];
    
    // Approved requests
    $result = $db->fetch("SELECT COUNT(*) as count FROM document_requests WHERE status = 'approved'");
    $stats['approved_requests'] = $result['count'];
    
    // Released requests
    $result = $db->fetch("SELECT COUNT(*) as count FROM document_requests WHERE status = 'released'");
    $stats['released_requests'] = $result['count'];
    
    // Rejected requests
    $result = $db->fetch("SELECT COUNT(*) as count FROM document_requests WHERE status = 'rejected'");
    $stats['rejected_requests'] = $result['count'];
    
    // Total residents
    $result = $db->fetch("SELECT COUNT(*) as count FROM residents");
    $stats['total_residents'] = $result['count'];
    
    // Pending residents
    $result = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'resident' AND status = 'pending'");
    $stats['pending_residents'] = $result['count'];
    
    return $stats;
}

/**
 * Send email notification (placeholder)
 */
function sendEmailNotification($to, $subject, $message) {
    // This is a placeholder. In a real application, you would use PHPMailer or similar
    // For now, we'll just log the email
    error_log("Email to: $to, Subject: $subject, Message: $message");
    return true;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = '') {
    global $db;
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    return $db->query($sql, [
        $userId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $url);
    exit();
}

/**
 * Get message from session
 */
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Display message
 */
function displayMessage($message, $type = 'info') {
    $alertClass = 'alert-' . $type;
    return "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}
?>
