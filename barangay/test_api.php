<?php
/**
 * Test the download API directly
 */

echo "<h2>Testing Download API</h2>";

// Test the API endpoint directly
$apiUrl = 'api/request_api.php?action=download_document&request_id=1';

echo "<p><strong>Testing API:</strong> $apiUrl</p>";

// Get the request details first
require_once 'includes/config.php';
require_once 'includes/functions.php';

$request = $db->fetch("SELECT * FROM document_requests WHERE request_number = 'BRG202508193ACD'");

if ($request) {
    echo "<p><strong>Request Details:</strong></p>";
    echo "<ul>";
    echo "<li>ID: " . $request['id'] . "</li>";
    echo "<li>Request Number: " . $request['request_number'] . "</li>";
    echo "<li>Status: " . $request['status'] . "</li>";
    echo "<li>Document File: " . ($request['document_file'] ?: 'NULL') . "</li>";
    echo "</ul>";
    
    // Test the API with the correct request ID
    $testApiUrl = 'api/request_api.php?action=download_document&request_id=' . $request['id'];
    echo "<p><strong>Test API URL:</strong> $testApiUrl</p>";
    
    // Check if file exists
    if (!empty($request['document_file'])) {
        $filePath = 'uploads/documents/' . $request['document_file'];
        if (file_exists($filePath)) {
            echo "<p><strong>✅ File exists:</strong> $filePath</p>";
            echo "<p><strong>File size:</strong> " . filesize($filePath) . " bytes</p>";
            
            // Test the API call
            echo "<p><strong>Testing API call...</strong></p>";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "<p><strong>HTTP Response Code:</strong> $httpCode</p>";
            echo "<p><strong>Response Headers:</strong></p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            
        } else {
            echo "<p><strong>❌ File not found:</strong> $filePath</p>";
        }
    } else {
        echo "<p><strong>❌ No document file linked to request</strong></p>";
    }
} else {
    echo "<p><strong>❌ Request not found</strong></p>";
}

echo "<hr>";
echo "<p><a href='test_download.php'>Run Full Test</a></p>";
echo "<p><a href='resident/request_status.php'>Go to Resident Page</a></p>";
?>
