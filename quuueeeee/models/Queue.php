<?php
class Queue {
    private $conn;
    private $table_name = "tickets";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getCurrentQueue() {
        $query = "SELECT t.ticket_number, s.name as student_name, 
                 sv.service_name as service, t.status, t.created_at
                 FROM " . $this->table_name . " t
                 JOIN students s ON t.student_id = s.id
                 JOIN services sv ON t.service_id = sv.id
                 WHERE t.status IN ('waiting', 'serving')
                 ORDER BY t.priority_level DESC, t.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCurrentQueueCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                 WHERE status IN ('waiting', 'serving')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    public function getServedTodayCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                 WHERE status = 'completed' 
                 AND DATE(completed_at) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    public function getAverageWaitTime() {
        $query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, called_at)) as avg_wait
                 FROM " . $this->table_name . "
                 WHERE status IN ('serving', 'completed')
                 AND DATE(created_at) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return round($row['avg_wait'] ?? 0);
    }

    public function getTotalStudentsCount() {
        $query = "SELECT COUNT(DISTINCT student_id) as count 
                 FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }
}
?>