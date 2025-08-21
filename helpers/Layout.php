<?php
class Layout {
    public static function render($content, $title = 'Dashboard', $role = null) {
        $role = $role ?? $_SESSION['role'] ?? 'staff';
        $menuItems = self::getMenuItems($role);
        
        include 'views/layouts/main.php';
    }
    
    private static function getMenuItems($role) {
        $items = [
            [
                'title' => 'Overview',
                'url' => 'dashboard.php',
                'icon' => 'fas fa-tachometer-alt',
                'active' => basename($_SERVER['PHP_SELF']) == 'dashboard.php'
            ],
            [
                'title' => 'POS',
                'url' => 'pos.php',
                'icon' => 'fas fa-shopping-cart',
                'active' => basename($_SERVER['PHP_SELF']) == 'pos.php'
            ],
            [
                'title' => 'Sales Reports',
                'url' => 'sales.php',
                'icon' => 'fas fa-chart-line',
                'active' => basename($_SERVER['PHP_SELF']) == 'sales.php'
            ]
        ];
        
        // Admin-only menu items
        if ($role === 'admin') {
            $adminItems = [
                [
                    'title' => 'Inventory',
                    'url' => 'inventory.php',
                    'icon' => 'fas fa-boxes',
                    'active' => basename($_SERVER['PHP_SELF']) == 'inventory.php'
                ],
              
            ];
            
            // Insert admin items after Overview
            array_splice($items, 1, 0, $adminItems);
        }
        
        return $items;
    }
    
    public static function formatCurrency($amount) {
        return '₱' . number_format($amount, 2);
    }
    
    public static function getTimeAgo($datetime) {
        $time_diff = time() - strtotime($datetime);
        if ($time_diff < 3600) {
            return floor($time_diff / 60) . 'min ago';
        } elseif ($time_diff < 86400) {
            return floor($time_diff / 3600) . 'h ago';
        } else {
            return date('M j, Y', strtotime($datetime));
        }
    }
}
?>
