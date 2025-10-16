<?php
require_once 'config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .welcome-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .brand-section {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            color: white;
            padding: 3rem;
            text-align: center;
        }
        .brand-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .action-section {
            padding: 3rem;
            text-align: center;
        }
        .btn-custom {
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0.5rem;
            min-width: 150px;
        }
        .btn-login {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            border: none;
            color: white;
        }
        .btn-register {
            background: transparent;
            border: 2px solid #dc3545;
            color: #dc3545;
        }
        .btn-login:hover, .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="welcome-card">
                    <div class="brand-section">
                        <i class="fas fa-cash-register fa-3x mb-3"></i>
                        <h1>PointShift</h1>
                        <p class="lead">Modern Point of Sale System</p>
                        <p>Streamline your business operations with role-based access</p>
                    </div>
                    <div class="action-section">
                        <h3 class="mb-4">Welcome to PointShift</h3>
                        <p class="text-muted mb-4">Sign in to access your dashboard.</p>
                        <div class="d-flex flex-column flex-sm-row justify-content-center">
                            <a href="login.php" class="btn btn-login btn-custom">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </a>
                        </div>
                        
                        <div class="demo-credentials">
                            <h6 class="text-dark mb-3">Demo Accounts:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="border-end">
                                        <h6 class="text-danger"><i class="fas fa-user-shield me-1"></i> Admin</h6>
                                        <small class="text-muted">
                                            Username: <strong>admin</strong><br>
                                            Password: <strong>admin123</strong>
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-success"><i class="fas fa-user me-1"></i> Staff</h6>
                                    <small class="text-muted">
                                        Username: <strong>staff</strong><br>
                                        Password: <strong>staff123</strong>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
