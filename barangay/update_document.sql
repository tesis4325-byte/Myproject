-- Update the document_requests table to link the test document
-- Replace '1' with the actual request ID if different

UPDATE document_requests 
SET document_file = 'test_document.txt', 
    updated_at = NOW() 
WHERE request_number = 'BRG202508193ACD';

-- Verify the update
SELECT id, request_number, status, document_file, updated_at 
FROM document_requests 
WHERE request_number = 'BRG202508193ACD';
