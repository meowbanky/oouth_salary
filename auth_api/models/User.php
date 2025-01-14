<?php
class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        try {
            // Log login attempt
            error_log("Starting login process for email: $email");

            $query = "SELECT 
    tbl_users.staff_id as id,  
    employee.`NAME` as name, 
    employee.EMAIL as email, 
    tbl_users.password_hash as password 
FROM tbl_users 
INNER JOIN employee ON tbl_users.staff_id = employee.staff_id 
WHERE 
    (employee.EMAIL = :email OR employee.MOBILE_NO = :mobile_no OR employee.alternate_email = :alternate_email)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':mobile_no', $email); // Using same value but different parameter name
            $stmt->bindParam(':alternate_email', $email); // Using same value but different parameter name

            $stmt->execute();

            error_log("Query executed, rows found: " . $stmt->rowCount());

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                error_log("User found, checking password");
                error_log("Stored password hash: " . $row['password']);
                error_log("Password being tested: " . $password);

                // Test password verification with debug info
                $verify_result = password_verify($password, $row['password']);
                error_log("Password verify result: " . ($verify_result ? 'true' : 'false'));

                if ($verify_result) {
                    error_log("Password verification successful");
                    return [
                        'success' => true,
                        'user' => [
                            'id' => $row['id'],
                            'name' => $row['name'],
                            'email' => $row['email'],
                        ]
                    ];
                } else {
                    error_log("Password verification failed");
                    // Additional debug info
                    if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                        error_log("Password hash needs rehash");
                    }
                }
            } else {
                error_log("No user found with email: $email");
            }

            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];

        } catch (PDOException $e) {
            error_log("Database error in login: " . $e->getMessage());
            throw new Exception('Database error occurred');
        }
    }

    public function saveDeviceId($userId, $deviceId) {
        $query = "UPDATE tbl_users SET device_id = :device_id WHERE staff_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':device_id', $deviceId);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }
}