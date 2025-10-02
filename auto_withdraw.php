<?php
require_once 'includes/config.php';
require_once 'includes/withdraw.php';

// 加强访问控制
$allowed = false;

// 命令行访问
if (php_sapi_name() === 'cli') {
    $allowed = true;
}

// 特定IP访问（支持IP段）
$allowed_ips = ['127.0.0.1', '192.168.1.0/24', '10.0.0.0/8'];
if (isset($_SERVER['REMOTE_ADDR'])) {
    $client_ip = $_SERVER['REMOTE_ADDR'];
    foreach ($allowed_ips as $allowed_ip) {
        if (strpos($allowed_ip, '/') !== false) {
            // CIDR格式检查
            if (ip_in_range($client_ip, $allowed_ip)) {
                $allowed = true;
                break;
            }
        } else {
            // 直接IP比较
            if ($client_ip === $allowed_ip) {
                $allowed = true;
                break;
            }
        }
    }
}

if (!$allowed) {
    header('HTTP/1.1 403 Forbidden');
    error_log("Unauthorized access attempt from: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    die('Access Denied');
}

// IP范围检查函数
function ip_in_range($ip, $range) {
    list($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask;
    return ($ip & $mask) == $subnet;
}

echo "开始执行自动提现申请生成...\n";
echo "执行时间: " . date('Y-m-d H:i:s') . "\n";

$results = autoWithdrawByCategory();

if ($results) {
    echo "提现申请生成完成！\n";
    foreach ($results as $result) {
        echo "分类: {$result['category']}, 金额: {$result['amount']}, 订单数: {$result['orders']}, 提现单号: {$result['withdraw_no']}\n";
    }
    echo "请登录后台进行审批操作。\n";
} else {
    echo "今日无收入或提现申请生成失败\n";
}

echo "自动提现执行完毕\n";
?>