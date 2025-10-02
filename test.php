<?php
/**
 * 直接SMTP测试
 */
$to = "2533448753@qq.com"; // 您的QQ邮箱
$subject = "SMTP直接测试 - " . date('Y-m-d H:i:s');

// 邮件内容
$message = "
<html>
<head>
    <title>SMTP测试邮件</title>
</head>
<body>
    <h2>SMTP直接测试</h2>
    <p>如果收到此邮件，说明SMTP配置正确。</p>
    <p>发送时间: " . date('Y-m-d H:i:s') . "</p>
</body>
</html>
";

// 邮件头
$headers = array(
    'From' => 'dof6086fu@qq.com',
    'Reply-To' => 'dof6086fu@qq.com',
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-type' => 'text/html; charset=utf-8'
);

// 构建header字符串
$header_string = '';
foreach ($headers as $key => $value) {
    $header_string .= "$key: $value\r\n";
}

echo "<h2>SMTP直接测试</h2>";
echo "<p>发送到: {$to}</p>";

// 发送邮件
$result = mail($to, $subject, $message, $header_string);

if ($result) {
    echo "<p style='color: green;'>✅ 邮件已提交到邮件队列</p>";
    echo "<p><strong>重要：请检查以下位置：</strong></p>";
    echo "<ol>
        <li>QQ邮箱的<strong>收件箱</strong></li>
        <li>QQ邮箱的<strong>垃圾邮件</strong>文件夹</li>
        <li>等待1-5分钟（邮件可能有延迟）</li>
        <li>检查发件邮箱 dof6086fu@qq.com 的已发送文件夹</li>
    </ol>";
} else {
    echo "<p style='color: red;'>❌ 邮件发送失败</p>";
    $error = error_get_last();
    echo "<p>错误: " . $error['message'] . "</p>";
}

echo "<hr><h3>下一步：</h3>";
echo "<p>1. 请立即检查 <strong>2533448753@qq.com</strong> 的垃圾邮件箱</p>";
echo "<p>2. 同时检查 <strong>dof6086fu@qq.com</strong> 的已发送邮件</p>";
echo "<p>3. 如果还是收不到，可能是服务器邮件功能被限制</p>";
?>