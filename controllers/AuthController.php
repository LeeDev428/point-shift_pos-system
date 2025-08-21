<?php
class AuthController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Please fill in all fields.'];
        }
        
        if ($this->user->login($username, $password)) {
            return ['success' => true, 'message' => 'Login successful.'];
        }
        
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
    
    public function register($data) {
        // Validation
        if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
            empty($data['first_name']) || empty($data['last_name'])) {
            return ['success' => false, 'message' => 'Please fill in all fields.'];
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }
        
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        
        if ($this->user->register($data)) {
            return ['success' => true, 'message' => 'Registration successful! You can now log in.'];
        }
        
        return ['success' => false, 'message' => 'Username or email already exists.'];
    }
    
    public function logout() {
        User::logout();
    }
}
?>
