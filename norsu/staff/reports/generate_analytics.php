<?php
require_once __DIR__ . '/../../config/database.php';

function generateElectionAnalytics($election_id) {
    global $conn;
    
    // Get hourly voting patterns
    $hourly_pattern = $conn->query("
        SELECT 
            HOUR(voted_at) as hour,
            COUNT(*) as vote_count
        FROM votes 
        WHERE election_id = $election_id
        GROUP BY HOUR(voted_at)
        ORDER BY hour
    ");
    
    // Get geographical voting distribution (requires adding a location field to users table)
    $geographical = $conn->query("
        SELECT 
            u.location,
            COUNT(DISTINCT v.voter_id) as voter_count
        FROM votes v
        JOIN users u ON v.voter_id = u.id
        WHERE v.election_id = $election_id
        GROUP BY u.location
    ");
    
    // Get demographic distribution (requires adding demographic fields to users table)
    $demographics = $conn->query("
        SELECT 
            u.age_group,
            u.gender,
            COUNT(DISTINCT v.voter_id) as voter_count
        FROM votes v
        JOIN users u ON v.voter_id = u.id
        WHERE v.election_id = $election_id
        GROUP BY u.age_group, u.gender
    ");
    
    // Get device usage statistics (requires adding a device_type field to votes table)
    $devices = $conn->query("
        SELECT 
            device_type,
            COUNT(*) as usage_count
        FROM votes
        WHERE election_id = $election_id
        GROUP BY device_type
    ");
    
    // Return analytics data
    return [
        'hourly_pattern' => $hourly_pattern->fetch_all(MYSQLI_ASSOC),
        'geographical' => $geographical->fetch_all(MYSQLI_ASSOC),
        'demographics' => $demographics->fetch_all(MYSQLI_ASSOC),
        'devices' => $devices->fetch_all(MYSQLI_ASSOC)
    ];
}

function generateSecurityReport($election_id) {
    global $conn;
    
    // Get security-related activities from audit logs
    $security_events = $conn->query("
        SELECT 
            al.*,
            u.username,
            u.role
        FROM audit_logs al
        JOIN users u ON al.user_id = u.id
        WHERE al.election_id = $election_id
        AND al.action_type IN ('login', 'failed_login', 'vote_cast', 'voter_approval', 'settings_change')
        ORDER BY al.created_at DESC
    ");
    
    // Get IP-based statistics (requires adding ip_address field to votes and audit_logs tables)
    $ip_stats = $conn->query("
        SELECT 
            ip_address,
            COUNT(*) as attempt_count,
            SUM(CASE WHEN action_type = 'failed_login' THEN 1 ELSE 0 END) as failed_attempts
        FROM audit_logs
        WHERE election_id = $election_id
        GROUP BY ip_address
        HAVING failed_attempts > 3
    ");
    
    // Return security data
    return [
        'security_events' => $security_events->fetch_all(MYSQLI_ASSOC),
        'ip_stats' => $ip_stats->fetch_all(MYSQLI_ASSOC)
    ];
}
