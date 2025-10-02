<?php
session_start();
require_once '../includes/config.php';

// 检查是否已经定义了 safe_input 函数，如果没有则定义
if (!function_exists('safe_input')) {
    function safe_input($data) {
        if (empty($data)) {
            return '';
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = safe_input($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['password'] === md5($password)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            
            // 记录登录日志
            if (function_exists('log_action')) {
                require_once '../includes/functions.php';
                log_action('管理员登录', '管理员 ' . $username . ' 登录系统');
            }
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = '用户名或密码错误';
            
            // 调试信息
            error_log("登录失败 - 用户名: " . $username . ", 输入密码MD5: " . md5($password));
            if ($user) {
                error_log("数据库中的密码: " . $user['password']);
            }
        }
    } catch (PDOException $e) {
        $error = '登录失败，请稍后重试';
        error_log("登录异常: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="login-header text-center">
                        <h3><i class="fas fa-lock me-2"></i>管理员登录</h3>
                        <p class="mb-0"><?php echo SITE_NAME; ?>后台管理系统</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">用户名</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
                                    <input type="text" name="username" class="form-control" placeholder="请输入用户名" required autofocus value="admin">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">密码</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="请输入密码" required value="admin123">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i>登录系统
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                默认账号: <strong>admin</strong> / <strong>admin123</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>