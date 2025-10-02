<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'super') {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

// 添加管理员
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] == 'add') {
    $username = safe_input($_POST['username']);
    $password = md5($_POST['password']);
    $email = safe_input($_POST['email']);
    $role = $_POST['role'];
    $bind_categories = isset($_POST['categories']) ? implode(',', $_POST['categories']) : '';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, role, bind_categories) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $password, $email, $role, $bind_categories])) {
            $_SESSION['success'] = '管理员添加成功！';
            header('Location: admin_manage.php');
            exit;
        }
    } catch (PDOException $e) {
        $error = '添加管理员失败: ' . $e->getMessage();
    }
}

// 获取所有管理员
$admins = $pdo->query("SELECT * FROM admin_users ORDER BY id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员管理 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <div class="col-md-10">
                <div class="p-4">
                    <h2>管理员管理</h2>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>

                    <!-- 添加管理员表单 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">添加新管理员</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" name="username" class="form-control" placeholder="用户名" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="password" name="password" class="form-control" placeholder="密码" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="email" name="email" class="form-control" placeholder="邮箱">
                                    </div>
                                    <div class="col-md-3">
                                        <select name="role" class="form-control" required>
                                            <option value="normal">普通管理员</option>
                                            <option value="super">超级管理员</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <label class="form-label">绑定分类（普通管理员有效）</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($categories as $category): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>">
                                                <label class="form-check-label"><?php echo htmlspecialchars($category['name']); ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">添加管理员</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 管理员列表 -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">管理员列表</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>用户名</th>
                                            <th>邮箱</th>
                                            <th>角色</th>
                                            <th>绑定分类</th>
                                            <th>状态</th>
                                            <th>创建时间</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($admins as $admin): ?>
                                        <tr>
                                            <td><?php echo $admin['id']; ?></td>
                                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $admin['role'] == 'super' ? 'danger' : 'primary'; ?>">
                                                    <?php echo $admin['role'] == 'super' ? '超级管理员' : '普通管理员'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($admin['bind_categories']): 
                                                    $bind_cats = explode(',', $admin['bind_categories']);
                                                    foreach ($bind_cats as $cat_id):
                                                        $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                                                        $cat_stmt->execute([$cat_id]);
                                                        $cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);
                                                ?>
                                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($cat['name']); ?></span>
                                                <?php endforeach; else: ?>
                                                    <span class="text-muted">未绑定</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $admin['status'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $admin['status'] ? '启用' : '禁用'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $admin['created_at']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>