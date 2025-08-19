<?php
/**
 * Resident Request API
 * Handles API calls for resident document requests
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Set JSON content type
header('Content-Type: application/json');

// Get the action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_request_details':
            $requestId = $_GET['request_id'] ?? $_POST['request_id'] ?? 0;
            if (!$requestId) {
                throw new Exception('Request ID is required');
            }
            
            // Debug logging
            error_log("API: get_request_details called with request_id: " . $requestId);
            
            // Get request details
            $request = getRequestById($requestId);
            if (!$request) {
                error_log("API: Request not found for ID: " . $requestId);
                throw new Exception('Request not found');
            }
            
            error_log("API: Request found: " . json_encode($request));
            
            // Check if current user owns this request
            $currentUser = $auth->getCurrentUser();
            if (!$currentUser) {
                throw new Exception('User not authenticated');
            }
            
            $resident = getResidentByUserId($currentUser['id']);
            if (!$resident || $resident['id'] != $request['resident_id']) {
                throw new Exception('Access denied');
            }
            
            // Format the response HTML
            $html = '
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-file-alt me-2"></i>Request Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Request Number:</strong></td><td>' . htmlspecialchars($request['request_number']) . '</td></tr>
                        <tr><td><strong>Document Type:</strong></td><td>' . htmlspecialchars($request['document_type']) . '</td></tr>
                        <tr><td><strong>Purpose:</strong></td><td>' . htmlspecialchars($request['purpose']) . '</td></tr>
                        <tr><td><strong>Status:</strong></td><td><span class="' . getStatusBadgeClass($request['status']) . '">' . getStatusText($request['status']) . '</span></td></tr>
                        <tr><td><strong>Submitted:</strong></td><td>' . formatDate($request['submitted_at']) . '</td></tr>
                        <tr><td><strong>Processing Fee:</strong></td><td>₱' . number_format($request['processing_fee'], 2) . '</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-user me-2"></i>Resident Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Name:</strong></td><td>' . htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) . '</td></tr>
                        <tr><td><strong>Address:</strong></td><td>' . htmlspecialchars($request['address']) . '</td></tr>
                        <tr><td><strong>Barangay:</strong></td><td>' . htmlspecialchars($request['barangay']) . '</td></tr>
                        <tr><td><strong>City:</strong></td><td>' . htmlspecialchars($request['city']) . '</td></tr>
                        <tr><td><strong>Province:</strong></td><td>' . htmlspecialchars($request['province']) . '</td></tr>
                    </table>
                </div>
            </div>';
            
            // Add notes if available
            if (!empty($request['admin_notes'])) {
                $html .= '<div class="mt-3"><h6><i class="fas fa-sticky-note me-2"></i>Admin Notes</h6><p class="text-muted">' . htmlspecialchars($request['admin_notes']) . '</p></div>';
            }
            
            if (!empty($request['resident_notes'])) {
                $html .= '<div class="mt-3"><h6><i class="fas fa-sticky-note me-2"></i>Your Notes</h6><p class="text-muted">' . htmlspecialchars($request['resident_notes']) . '</p></div>';
            }
            
            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
            break;
            
        case 'download_document':
            $requestId = $_GET['request_id'] ?? 0;
            if (!$requestId) {
                throw new Exception('Request ID is required');
            }
            
            // Debug logging
            error_log("API: download_document called with request_id: " . $requestId);
            
            // Get request details
            $request = getRequestById($requestId);
            if (!$request) {
                error_log("API: Request not found for ID: " . $requestId);
                throw new Exception('Request not found');
            }
            
            // Debug: Log the request data
            error_log("API: Request data: " . json_encode($request));
            
            // Check if current user owns this request
            $currentUser = $auth->getCurrentUser();
            if (!$currentUser) {
                throw new Exception('User not authenticated');
            }
            
            $resident = getResidentByUserId($currentUser['id']);
            if (!$resident || $resident['id'] != $request['resident_id']) {
                throw new Exception('Access denied');
            }
            
            // Check if request is approved or released
            if ($request['status'] !== 'approved' && $request['status'] !== 'released') {
                error_log("API: Request status not allowed: " . $request['status']);
                throw new Exception('Document is not available for download');
            }
            
            // Check if document file exists
            if (empty($request['document_file'])) {
                error_log("API: document_file is empty for request ID: " . $requestId);
                throw new Exception('Document file not found - the document has not been generated yet. Please contact the barangay office.');
            }
            
            // Set headers for file download
            $filePath = '../uploads/documents/' . $request['document_file'];
            error_log("API: Looking for file at: " . $filePath);
            
            // Check if uploads directory exists
            $uploadsDir = '../uploads/documents/';
            if (!is_dir($uploadsDir)) {
                error_log("API: Uploads directory does not exist: " . $uploadsDir);
                throw new Exception('Document storage directory not found. Please contact the barangay office.');
            }
            
            if (!file_exists($filePath)) {
                error_log("API: File does not exist at path: " . $filePath);
                throw new Exception('Document file not found on server. The document may still be processing. Please contact the barangay office.');
            }
            
            $fileName = $request['document_type'] . '_' . $request['request_number'] . '.pdf';
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            readfile($filePath);
            exit;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
