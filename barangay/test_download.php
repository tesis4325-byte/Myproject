<?php
/**
 * Test script to check and fix document download issue
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h2>Document Download Test</h2>";

// Check the request
$request = $db->fetch("SELECT * FROM document_requests WHERE request_number = 'BRG202508193ACD'");

if ($request) {
    echo "<p><strong>Request found:</strong></p>";
    echo "<ul>";
    echo "<li>ID: " . $request['id'] . "</li>";
    echo "<li>Request Number: " . $request['request_number'] . "</li>";
    echo "<li>Status: " . $request['status'] . "</li>";
    echo "<li>Document File: " . ($request['document_file'] ?: 'NULL') . "</li>";
    echo "</ul>";
    
    // Check if document file exists
    if (empty($request['document_file'])) {
        echo "<p><strong>Issue:</strong> No document file linked to this request.</p>";
        
        // Update with test document
        $result = $db->query("UPDATE document_requests SET document_file = 'test_document.txt', updated_at = NOW() WHERE id = ?", [$request['id']]);
        
        if ($result) {
            echo "<p><strong>Fixed:</strong> Linked test document to request.</p>";
            
            // Verify the update
            $updatedRequest = $db->fetch("SELECT * FROM document_requests WHERE id = ?", [$request['id']]);
            echo "<p><strong>Updated Document File:</strong> " . $updatedRequest['document_file'] . "</p>";
        } else {
            echo "<p><strong>Error:</strong> Failed to update database.</p>";
        }
    } else {
        echo "<p><strong>Document file exists:</strong> " . $request['document_file'] . "</p>";
        
        // Check if file exists on disk
        $filePath = 'uploads/documents/' . $request['document_file'];
        if (file_exists($filePath)) {
            echo "<p><strong>File exists on disk:</strong> Yes</p>";
            echo "<p><strong>File size:</strong> " . filesize($filePath) . " bytes</p>";
        } else {
            echo "<p><strong>File exists on disk:</strong> No</p>";
        }
    }
} else {
    echo "<p><strong>Error:</strong> Request not found.</p>";
}

echo "<hr>";
echo "<p><a href='resident/request_status.php'>Go to Resident Request Status</a></p>";
echo "<p><a href='admin/requests.php'>Go to Admin Requests</a></p>";
?>
