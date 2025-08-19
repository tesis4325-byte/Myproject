<?php
/**
 * Admin API Endpoint
 * Handle AJAX requests from admin pages
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure admin access
$auth->requireAdmin();

// Set JSON response header
header('Content-Type: application/json');

// Get the action
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_request_details':
            $requestId = $_POST['request_id'] ?? 0;
            if (!$requestId) {
                throw new Exception('Request ID is required');
            }
            
            $request = getRequestById($requestId);
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            echo json_encode([
                'success' => true,
                'request' => $request
            ]);
            break;
            
        case 'update_request_status':
            $requestId = $_POST['request_id'] ?? 0;
            $newStatus = $_POST['new_status'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            // Debug logging
            error_log("API: update_request_status called with request_id: $requestId, new_status: $newStatus, notes: $notes");
            
            if (!$requestId || !$newStatus) {
                throw new Exception('Request ID and new status are required');
            }
            
            // Validate status
            $validStatuses = ['pending', 'processing', 'approved', 'released', 'rejected'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            // Update the request status
            $sql = "UPDATE document_requests SET status = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$newStatus, $requestId]);
            
            // Set specific timestamps based on status
            if ($newStatus === 'approved') {
                $sql = "UPDATE document_requests SET approved_at = NOW() WHERE id = ?";
                $db->query($sql, [$requestId]);
            } elseif ($newStatus === 'released') {
                $sql = "UPDATE document_requests SET released_at = NOW() WHERE id = ?";
                $db->query($sql, [$requestId]);
            } elseif ($newStatus === 'rejected') {
                $sql = "UPDATE document_requests SET rejected_at = NOW() WHERE id = ?";
                $db->query($sql, [$requestId]);
            }
            
            // Add status change note if provided
            if ($notes) {
                try {
                    $sql = "INSERT INTO request_notes (request_id, note, created_at) VALUES (?, ?, NOW())";
                    $db->query($sql, [$requestId, $notes]);
                } catch (Exception $noteError) {
                    // Log the error but don't fail the status update
                    error_log("Failed to add status note: " . $noteError->getMessage());
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
            break;
            
        case 'release_document':
            $requestId = $_POST['request_id'] ?? 0;
            if (!$requestId) {
                throw new Exception('Request ID is required');
            }
            
            // Check if request is approved
            $request = getRequestById($requestId);
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            if ($request['status'] !== 'approved') {
                throw new Exception('Only approved requests can be released');
            }
            
            // Update status to released
            $sql = "UPDATE document_requests SET status = 'released', updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$requestId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Document marked as released successfully'
            ]);
            break;
            
        case 'upload_document':
            $requestId = $_POST['request_id'] ?? 0;
            if (!$requestId) {
                throw new Exception('Request ID is required');
            }
            
            // Check if request exists
            $request = getRequestById($requestId);
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            // Check if file was uploaded
            if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Please select a valid document file');
            }
            
            $file = $_FILES['document_file'];
            
            // Validate file
            if (!validateFileUpload($file)) {
                throw new Exception('Invalid file. Please upload a valid PDF, DOC, or DOCX file (max 5MB)');
            }
            
            // Create uploads directory if it doesn't exist
            $uploadDir = '../uploads/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'doc_' . $requestId . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to upload file. Please try again.');
            }
            
            // Update database with filename
            $sql = "UPDATE document_requests SET document_file = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$filename, $requestId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'filename' => $filename
            ]);
            break;
            
        case 'approve_resident':
            $userId = $_POST['user_id'] ?? 0;
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            
            // Update user status to active
            $sql = "UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ? AND role = 'resident'";
            $result = $db->query($sql, [$userId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Resident approved successfully'
                ]);
            } else {
                throw new Exception('Failed to approve resident');
            }
            break;
            
        case 'reject_resident':
            $userId = $_POST['user_id'] ?? 0;
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            
            // Update user status to rejected
            $sql = "UPDATE users SET status = 'rejected', updated_at = NOW() WHERE id = ? AND role = 'resident'";
            $result = $db->query($sql, [$userId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Resident rejected successfully'
                ]);
            } else {
                throw new Exception('Failed to reject resident');
            }
            break;
            
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
