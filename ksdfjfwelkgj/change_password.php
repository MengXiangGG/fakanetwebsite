<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_id = $_SESSION['admin_id'];
    
    // 验证输入
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = '请填写所有字段';
    } elseif ($new_password !== $confirm_password) {
        $error = '新密码与确认密码不一致';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码长度至少6位';
    } else {
        try {
            // 获取当前用户信息
            $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->execute([$admin_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user['password'] === md5($current_password)) {
                // 更新密码（使用MD5加密）
                $hashed_password = md5($new_password);
                $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $admin_id]);
                
                $success = '密码修改成功！';
                
                // 记录操作日志
                log_action('修改密码', '管理员修改了登录密码');
                
            } else {
                $error = '当前密码错误';
            }
        } catch (PDOException $e) {
            $error = '修改密码失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 bg-dark text-white min-vh-100 p-0">
                <?php include 'sidebar.php'; ?>
            </div>
            
            <!-- 主要内容 -->
            <div class="col-md-10">
                <div class="p-4">
                    <h2>修改密码</h2>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">修改管理员密码</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label class="form-label">当前密码</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                        <input type="password" name="current_password" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">新密码</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                                    </div>
                                                    <div class="form-text">密码长度至少6位</div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">确认新密码</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-key me-2"></i>修改密码
                                                </button>
                                                <a href="dashboard.php" class="btn btn-secondary">取消</a>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-info-circle me-2"></i>密码安全提示：</h6>
                                                    <ul class="mb-0 small">
                                                        <li>使用至少6位字符的密码</li>
                                                        <li>建议包含字母、数字和特殊字符</li>
                                                        <li>定期更换密码以提高安全性</li>
                                                        <li>不要使用与其他网站相同的密码</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>