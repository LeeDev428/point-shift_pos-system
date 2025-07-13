<?php
require_once 'config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $result = $authController->login($_POST['username'], $_POST['password']);
    
if ($result['success']) {
    // Redirect based on role
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if ($role === 'admin') {
        redirect('dashboard.php');
    } elseif ($role === 'staff') {
        redirect('staff/dashboard.php');
    } elseif ($role === 'cashier') {
        redirect('cashier/dashboard.php');
    } else {
        redirect('dashboard.php'); // fallback
    }
} else {
    $error_message = $result['message'];
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            margin: 0 auto;
        }
        .login-header {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-user-circle fa-3x mb-3"></i>
                        <h3>Welcome Back</h3>
                        <p class="mb-0">Sign in to your account</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Don't have an account? 
                                <a href="register.php" class="text-decoration-none">Register here</a>
                            </small>
                        </div>
                        
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                <a href="index.php" class="text-decoration-none">← Back to Home</a>
                            </small>
                        </div>

                        <!-- Demo credentials -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <small class="text-muted">
                                <strong>Demo Credentials:</strong><br>
                                <span class="d-block mb-1">Admin: <b>admin</b> / <b>admin123</b></span>
                                <span class="d-block mb-1">Staff: <b>staff</b> / <b>staff123</b></span>
                                <span class="d-block mb-1">Cashier: <b>cashier</b> / <b>cashier123</b></span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
