<?php
class User {
    private $db;
    private $id;
    private $username;
    private $email;
    private $role;
    private $firstName;
    private $lastName;
    private $status;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $this->setUserData($user);
                $this->setSession();
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function register($data) {
        try {
            // Check if username or email exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$data['username'], $data['email']]);
            
            if ($stmt->fetch()) {
                return false;
            }
            
            // Insert new user
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'staff')");
            $stmt->execute([
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name']
            ]);
            
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: dashboard.php');
            exit();
        }
    }
    
    public static function logout() {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    private function setUserData($user) {
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->email = $user['email'];
        $this->role = $user['role'];
        $this->firstName = $user['first_name'];
        $this->lastName = $user['last_name'];
        $this->status = $user['status'];
    }
    
    private function setSession() {
        $_SESSION['user_id'] = $this->id;
        $_SESSION['username'] = $this->username;
        $_SESSION['role'] = $this->role;
        $_SESSION['first_name'] = $this->firstName;
        $_SESSION['last_name'] = $this->lastName;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getFirstName() { return $this->firstName; }
    public function getLastName() { return $this->lastName; }
    public function getFullName() { return $this->firstName . ' ' . $this->lastName; }
    public function getStatus() { return $this->status; }
}
?>
