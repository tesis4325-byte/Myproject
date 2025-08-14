<?php
class Ticket {
    private $conn;
    private $table_name = "tickets";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function serve($ticket_number) {
        $query = "UPDATE " . $this->table_name . "
                 SET status = 'serving', called_at = NOW()
                 WHERE ticket_number = :ticket_number
                 AND status = 'waiting'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ticket_number", $ticket_number);
        return $stmt->execute();
    }

    public function complete($ticket_number) {
        $query = "UPDATE " . $this->table_name . "
                 SET status = 'completed', completed_at = NOW()
                 WHERE ticket_number = :ticket_number
                 AND status = 'serving'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ticket_number", $ticket_number);
        return $stmt->execute();
    }
}
?>