
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, #dc3545 0%, #b02a37 100%);
            min-height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s;
        }
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: bold;
        }
        .sidebar-header small {
            color: rgba(255,255,255,0.8);
        }
        .sidebar-menu {
            padding: 1rem 0;
        }
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0;
            transition: all 0.3s;
            margin-bottom: 0.25rem;
        }
        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        .sidebar-menu .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }
        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        .pos-container {
            padding: 0;
            height: calc(100vh - 70px);
            overflow: hidden;
        }
        .cart-section {
            background: white;
            border-right: 1px solid #dee2e6;
            height: 100%;
            padding: 1.5rem;
        }
        .products-section {
            background: #f8f9fa;
            height: 100%;
            padding: 1.5rem;
        }
        .cart-header {
            background: #f8f9fa;
            padding: 1rem;
            margin: -1.5rem -1.5rem 1rem -1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 0.75rem 0;
        }
        .cart-summary {
            border-top: 2px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        .product-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .category-tabs .nav-link {
            background: #e9ecef;
            color: #6c757d;
            border: none;
            margin-right: 0.5rem;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
        }
        .category-tabs .nav-link.active {
            background: #007bff;
            color: white;
        }
        .btn-add-cart {
            background: #28a745;
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-complete-sale {
            background: #28a745;
            border: none;
            color: white;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .payment-buttons button {
            margin: 0.25rem;
            min-width: 80px;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-cash-register fa-2x text-white mb-2"></i>
            <h4>PointShift</h4>
            <small>Cashier Panel</small>
        </div>
        
        <nav class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>" href="pos.php">
                        <i class="fas fa-shopping-cart"></i>
                        Point of Sale
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                        <i class="fas fa-receipt"></i>
                        Transaction History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'email.php' ? 'active' : ''; ?>" href="email.php">
                        <i class="fas fa-envelope"></i>
                        Email Admin
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_shifts.php' ? 'active' : ''; ?>" href="view_shifts.php">
                        <i class="fas fa-calendar-alt"></i>
                        View Shifts
                    </a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'account_settings.php' ? 'active' : ''; ?>" href="account_settings.php">
                        <i class="fas fa-user-cog"></i>
                        Account Settings
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-light d-md-none me-3" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h5>
            </div>
            
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php 
                    // Check if session variables are missing and refresh them from database
                    if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
                        try {
                            $db = Database::getInstance()->getConnection();
                            $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($user) {
                                $_SESSION['first_name'] = $user['first_name'];
                                $_SESSION['last_name'] = $user['last_name'];
                            }
                        } catch(Exception $e) {
                            // Fallback if database query fails
                        }
                    }
                    
                    $firstName = $_SESSION['first_name'] ?? $_SESSION['username'] ?? 'User';
                    $lastName = $_SESSION['last_name'] ?? '';
                    echo htmlspecialchars(trim($firstName . ' ' . $lastName)); 
                    ?>
                    <span class="badge bg-success ms-2">Cashier</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid pos-container">
            <?php echo $content; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.getElementById('sidebar-toggle');
                
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>
