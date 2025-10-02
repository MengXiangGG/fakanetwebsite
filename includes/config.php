<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'test_faka_com');
define('DB_PASS', 'zYW7Ndc5rZnCFFsc');
define('DB_NAME', 'test_faka_com');



// 易支付配置
define('EPAY_URL', 'http://123.206.104.34:7411/');
define('EPAY_PID', '1000');
define('EPAY_KEY', '0pvOpLY76ZL3PU6tovZ0VP563j0031pC');
define('TRANSACTION_FEE_RATE', 0.006); // 千分之6的费率
// 邮件配置
define('SMTP_HOST', 'smtp.qq.com');
define('SMTP_PORT', 465);  // 使用465端口
define('SMTP_USERNAME', 'dof6086fu@qq.com');
define('SMTP_PASSWORD', 'ydpumdsrjbjrdhji');
define('FROM_EMAIL', 'dof6086fu@qq.com');
define('FROM_NAME', '自动发卡网');

// 邮件通知开关
define('EMAIL_NOTIFY_ENABLED', true);
define('EMAIL_NOTIFY_PAYMENT', true);

// 网站配置
define('SITE_URL', 'http://123.206.104.34:10005/');
define('SITE_NAME', '聚财自助发卡网');

// 创建数据库连接
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
?>