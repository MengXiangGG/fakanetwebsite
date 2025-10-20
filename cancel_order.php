<?php
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_no = safe_input($_POST['order_no']);
    
    try {
        // 获取订单信息
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ? AND status = 0");
        $stmt->execute([$order_no]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // 更新订单状态为已取消
            $stmt = $pdo->prepare("UPDATE orders SET status = 3 WHERE order_no = ?");
            $stmt->execute([$order_no]);
            
            echo json_encode(['success' => true, 'message' => '订单取消成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '订单不存在或无法取消']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '取消订单失败：' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => '无效请求']);
}
?>