<?php
// config/Database.php

class Database {
    private $host = 'localhost';
    private $db_name = 'oouthsal_salary3';
    private $username = 'oouthsal_root';
    private $password = 'Oluwaseyi@7980';
    private $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
}