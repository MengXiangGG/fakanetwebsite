<?php
require_once 'includes/functions.php';
require_once 'includes/epay.php';

header('Content-Type: application/json');

$order_no = isset($_GET['order_no']) ? $_GET['order_no'] : '';

if (empty($order_no)) {
    echo json_encode(['paid' => false]);
    exit;
}

// 首先检查本地数据库状态
$stmt = $pdo->prepare("SELECT status FROM orders WHERE order_no = ?");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order && $order['status'] == 1) {
    echo json_encode(['paid' => true]);
    exit;
}

// 如果本地状态未支付，查询支付平台
$result = queryEpayOrder($order_no);

if ($result && isset($result['status']) && $result['status'] == 1) {
    // 支付平台显示已支付，更新本地状态
    handlePaymentSuccess($order_no, $result['type'] ?? '');
    echo json_encode(['paid' => true]);
} else {
    echo json_encode(['paid' => false]);
}
?>