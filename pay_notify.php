<?php
require_once 'includes/functions.php';
require_once 'includes/epay.php';

// 记录回调请求
error_log("=== 收到易支付异步回调 ===");
error_log("GET参数: " . print_r($_GET, true));

// 验证签名
if (verifyEpayNotify()) {
    $order_no = $_GET['out_trade_no'];
    $trade_status = $_GET['trade_status'];
    
    error_log("回调验证成功 - 订单号: {$order_no}, 状态: {$trade_status}");
    
    if ($trade_status === 'TRADE_SUCCESS') {
        // 处理支付成功
        if (handlePaymentSuccess($order_no, $_GET['type'])) {
            error_log("支付成功处理完成 - 订单号: {$order_no}");
            echo 'success';
            exit;
        } else {
            error_log("支付成功处理失败 - 订单号: {$order_no}");
        }
    } else {
        error_log("支付状态不是成功 - 订单号: {$order_no}, 状态: {$trade_status}");
    }
} else {
    error_log("异步回调签名验证失败");
    error_log("GET参数: " . print_r($_GET, true));
}

echo 'fail';
?>