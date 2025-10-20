<?php
/**
 * 每日0点自动提现脚本
 * 此脚本由Cron Job调用，每天0点执行
 */

require_once 'includes/config.php';
require_once 'includes/withdraw.php';

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 日志文件路径
$log_file = __DIR__ . '/logs/auto_withdraw.log';

// 创建日志目录
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// 日志函数
function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    echo $log_entry;
}

// 开始执行
log_message("=== 开始执行每日自动提现 ===");

try {
    // 检查是否有余额的分类
    $categories_with_balance = $pdo->query("
        SELECT c.id, c.name, c.balance 
        FROM categories c 
        WHERE c.status = 1 AND c.balance > 0
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($categories_with_balance)) {
        log_message("没有找到有余额的分类，跳过提现");
        exit(0);
    }

    log_message("找到 " . count($categories_with_balance) . " 个有余额的分类");

    $total_withdraw_amount = 0;
    $success_count = 0;

    foreach ($categories_with_balance as $category) {
        log_message("处理分类: {$category['name']}, 余额: {$category['balance']}");
        
        // 开始事务
        $pdo->beginTransaction();
        
        try {
            // 计算该分类今日订单数量
            $order_stmt = $pdo->prepare("
                SELECT COUNT(o.id) as order_count 
                FROM orders o 
                LEFT JOIN products p ON o.product_id = p.id 
                WHERE p.category_id = ? 
                AND o.status = 1 
                AND DATE(o.paid_at) = CURDATE()
            ");
            $order_stmt->execute([$category['id']]);
            $order_result = $order_stmt->fetch(PDO::FETCH_ASSOC);
            $order_count = $order_result['order_count'] ?: 0;

            // 全额提现（无手续费）
            $withdraw_amount = $category['balance'];
            
            // 创建提现申请
            $withdraw_no = addWithdrawApplication(
                $category['id'], 
                $withdraw_amount, 
                '系统自动', 
                '', 
                '', 
                'alipay'
            );
            
            if ($withdraw_no) {
                // 清零余额
                $stmt = $pdo->prepare("UPDATE categories SET balance = 0 WHERE id = ?");
                $stmt->execute([$category['id']]);
                
                $pdo->commit();
                
                $total_withdraw_amount += $withdraw_amount;
                $success_count++;
                
                log_message("✓ 分类 {$category['name']} 提现成功 - 金额: {$withdraw_amount}, 订单数: {$order_count}, 提现单号: {$withdraw_no}");
            } else {
                $pdo->rollBack();
                log_message("✗ 分类 {$category['name']} 提现申请创建失败");
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            log_message("✗ 分类 {$category['name']} 处理失败: " . $e->getMessage());
        }
    }

    log_message("=== 自动提现完成 ===");
    log_message("成功处理: {$success_count} 个分类");
    log_message("总提现金额: {$total_withdraw_amount}");
    log_message("执行时间: " . date('Y-m-d H:i:s'));

} catch (Exception $e) {
    log_message("!!! 系统错误: " . $e->getMessage());
}
?>