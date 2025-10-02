<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 只有通过命令行或特定IP可以访问
$allowed = false;

// 命令行访问
if (php_sapi_name() === 'cli') {
    $allowed = true;
}

// 特定IP访问（用于计划任务）
if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
    $allowed = true;
}

if (!$allowed) {
    die('Access Denied');
}

echo "开始执行自动取消未支付订单...\n";
echo "执行时间: " . date('Y-m-d H:i:s') . "\n";

try {
    // 取消24小时前未支付的订单
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 3 
        WHERE status = 0 
        AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    
    $canceled_count = $stmt->rowCount();
    
    echo "成功取消 {$canceled_count} 个未支付订单\n";
    
    // 记录日志
    if (function_exists('log_action')) {
        log_action('自动取消订单', "系统自动取消了 {$canceled_count} 个超过24小时未支付的订单");
    }
    
} catch (PDOException $e) {
    echo "取消订单失败: " . $e->getMessage() . "\n";
    error_log("自动取消订单失败: " . $e->getMessage());
}

echo "自动取消订单执行完毕\n";
?>