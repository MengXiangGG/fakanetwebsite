<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';
require_once '../includes/mailer.php';

$error = '';
$success = '';
$test_result = '';

// 保存邮件配置
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // 这里可以添加保存邮件配置到数据库的功能
    $success = '邮件配置已更新！';
}

// 测试邮件发送
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $test_email = $_POST['test_email'];
    
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        if ($mailer->testEmailConfig($test_email)) {
            $test_result = '<div class="alert alert-success">测试邮件发送成功！请检查邮箱。</div>';
        } else {
            $test_result = '<div class="alert alert-danger">测试邮件发送失败，请检查邮件配置。</div>';
        }
    } else {
        $test_result = '<div class="alert alert-warning">请输入有效的邮箱地址。</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮件设置 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <div class="col-md-10">
                <div class="p-4">
                    <h2>邮件通知设置</h2>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php echo $test_result; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">邮件配置</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">SMTP服务器</label>
                                            <input type="text" class="form-control" value="<?php echo SMTP_HOST; ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP端口</label>
                                            <input type="text" class="form-control" value="<?php echo SMTP_PORT; ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">发件人邮箱</label>
                                            <input type="text" class="form-control" value="<?php echo FROM_EMAIL; ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">发件人名称</label>
                                            <input type="text" class="form-control" value="<?php echo FROM_NAME; ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="emailEnabled" <?php echo EMAIL_NOTIFY_ENABLED ? 'checked' : ''; ?> disabled>
                                                <label class="form-check-label" for="emailEnabled">启用邮件通知</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="paymentEmail" <?php echo EMAIL_NOTIFY_PAYMENT ? 'checked' : ''; ?> disabled>
                                                <label class="form-check-label" for="paymentEmail">支付成功邮件通知</label>
                                            </div>
                                        </div>
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-info-circle me-2"></i>
                                                邮件配置需要在 includes/config.php 文件中修改。当前为演示配置。
                                            </small>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">测试邮件发送</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">测试邮箱地址</label>
                                            <input type="email" name="test_email" class="form-control" placeholder="输入邮箱地址进行测试" required>
                                            <div class="form-text">将发送一封测试邮件到指定邮箱</div>
                                        </div>
                                        <button type="submit" class="btn btn-primary" name="send_test">
                                            <i class="fas fa-paper-plane me-2"></i>发送测试邮件
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">功能说明</h5>
                                </div>
                                <div class="card-body">
                                    <h6>邮件通知功能：</h6>
                                    <ul class="small">
                                        <li>支付成功后自动发送卡密到客户邮箱</li>
                                        <li>支持HTML格式的邮件模板</li>
                                        <li>包含订单信息和卡密详情</li>
                                        <li>提升客户体验和售后服务</li>
                                    </ul>
                                    
                                    <h6 class="mt-3">配置说明：</h6>
                                    <ul class="small">
                                        <li>修改 includes/config.php 中的邮件配置</li>
                                        <li>推荐使用QQ邮箱、163邮箱等</li>
                                        <li>需要开启SMTP服务并获取授权码</li>
                                        <li>测试配置确保邮件发送正常</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>