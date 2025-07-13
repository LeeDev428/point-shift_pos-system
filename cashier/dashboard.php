<?php
$requireLogin = require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}

$title = 'Cashier Dashboard';
$db = Database::getInstance()->getConnection();
$today = date('Y-m-d');
$salesToday = $db->query("SELECT SUM(total_amount) FROM orders WHERE DATE(created_at) = '$today' AND status = 'completed'")->fetchColumn() ?? 0;
$transactionCount = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = '$today'")->fetchColumn() ?? 0;
$topProducts = $db->query("SELECT p.name, SUM(oi.quantity) as qty FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) = '$today' GROUP BY p.id ORDER BY qty DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$content = '<div class="container py-4">
  <h2 class="mb-4">Today\'s Sales Overview</h2>
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <h5>Total Sales</h5>
          <h3 class="text-success">₱'.number_format($salesToday,2).'</h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <h5>Transactions</h5>
          <h3 class="text-primary">'.$transactionCount.'</h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <h5>Top Products</h5>
          <ul class="list-group">'.
            (count($topProducts) ? implode('', array_map(function($p){
              return '<li class="list-group-item d-flex justify-content-between align-items-center">'.htmlspecialchars($p['name']).'<span class="badge bg-info">'.$p['qty'].'</span></li>';
            }, $topProducts)) : '<li class="list-group-item">No sales yet</li>').
          '</ul>
        </div>
      </div>
    </div>
  </div>
</div>';
include __DIR__ . '/views/layout.php';
