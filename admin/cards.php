<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// 添加卡密
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] == 'add') {
    $product_id = intval($_POST['product_id']);
    $cards_text = trim($_POST['cards']);
    
    if (empty($cards_text)) {
        $error = '请输入卡密内容';
    } else {
        // 使用空格分隔卡密
        $cards = preg_split('/\s+/', $cards_text);
        $success_count = 0;
        $error_count = 0;
        $duplicate_count = 0;
        
        foreach ($cards as $card_line) {
            $card_line = trim($card_line);
            if (!empty($card_line)) {
                // 使用空格分隔卡号和密码
                $parts = preg_split('/\s+/', $card_line, 2);
                $card_number = trim($parts[0]);
                $card_password = isset($parts[1]) ? trim($parts[1]) : '';
                
                // 检查卡号是否已存在
                $check_stmt = $pdo->prepare("SELECT id FROM cards WHERE card_number = ? AND product_id = ?");
                $check_stmt->execute([$card_number, $product_id]);
                
                if ($check_stmt->fetch()) {
                    $duplicate_count++;
                    continue;
                }
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO cards (product_id, card_number, card_password) VALUES (?, ?, ?)");
                    $stmt->execute([$product_id, $card_number, $card_password]);
                    $success_count++;
                } catch (PDOException $e) {
                    $error_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['success'] = "成功添加 {$success_count} 个卡密";
            if ($error_count > 0) {
                $_SESSION['success'] .= "，{$error_count} 个添加失败";
            }
            if ($duplicate_count > 0) {
                $_SESSION['success'] .= "，{$duplicate_count} 个重复卡密已跳过";
            }
        } else {
            $error = "添加失败，请检查卡密格式";
            if ($duplicate_count > 0) {
                $error .= "（发现 {$duplicate_count} 个重复卡密）";
            }
        }
        
        header('Location: cards.php?product_id=' . $product_id);
        exit;
    }
}

// 删除卡密
if (isset($_GET['delete_card'])) {
    $card_id = intval($_GET['delete_card']);
    
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM cards WHERE id = ?");
        $stmt->execute([$card_id]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($card) {
            $stmt = $pdo->prepare("DELETE FROM cards WHERE id = ?");
            $stmt->execute([$card_id]);
            
            $_SESSION['success'] = '卡密删除成功！';
        }
        
        header('Location: cards.php?product_id=' . $card['product_id']);
        exit;
    } catch (PDOException $e) {
        $error = '删除卡密失败: ' . $e->getMessage();
    }
}

// 批量删除卡密
if (isset($_POST['action']) && $_POST['action'] == 'batch_delete') {
    $product_id = intval($_POST['product_id']);
    $card_ids = isset($_POST['card_ids']) ? $_POST['card_ids'] : [];
    
    if (!empty($card_ids)) {
        $placeholders = str_repeat('?,', count($card_ids) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM cards WHERE id IN ($placeholders)");
        $stmt->execute($card_ids);
        
        $_SESSION['success'] = '批量删除成功！';
    }
    
    header('Location: cards.php?product_id=' . $product_id);
    exit;
}

// 获取商品信息
if ($product_id) {
    $product = getProductById($product_id);
    if (!$product) {
        header('Location: cards.php');
        exit;
    }
}

// 获取卡密列表
if ($product_id) {
    $cards_stmt = $pdo->prepare("SELECT * FROM cards WHERE product_id = ? ORDER BY status ASC, id DESC");
    $cards_stmt->execute([$product_id]);
    $cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 获取商品列表
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY p.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>卡密管理 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card-example {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .batch-actions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
        }
    </style>
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
                    <h2>卡密管理</h2>
                    
                    <!-- 显示消息 -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- 选择商品 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">选择商品</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET">
                                <div class="row">
                                    <div class="col-md-8">
                                        <select name="product_id" class="form-control" onchange="this.form.submit()">
                                            <option value="">请选择商品...</option>
                                            <?php foreach ($products as $prod): ?>
                                            <option value="<?php echo $prod['id']; ?>" <?php echo $product_id == $prod['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($prod['name']); ?> 
                                                (分类: <?php echo htmlspecialchars($prod['category_name']); ?>,
                                                库存: <?php echo getProductStock($prod['id']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="products.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-plus"></i> 添加新商品
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($product_id && $product): ?>
                    <!-- 商品信息 -->
                    <div class="card-info-box">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">
                                    <i class="fas fa-box me-2"></i>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h4>
                                <p class="mb-1"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="d-flex gap-4">
                                    <div>
                                        <small>价格: <strong>¥<?php echo number_format($product['price'], 2); ?></strong></small>
                                    </div>
                                    <div>
                                        <small>分类: <strong><?php echo htmlspecialchars($product['category_name']); ?></strong></small>
                                    </div>
                                    <div>
                                        <small>当前库存: <strong><?php echo getProductStock($product_id); ?></strong></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-<?php echo getProductStock($product_id) > 0 ? 'success' : 'danger'; ?> fs-6">
                                    库存: <?php echo getProductStock($product_id); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 添加卡密 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">添加卡密</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">
                                        卡密内容（每行一个卡密，使用空格分隔卡号和密码）
                                    </label>
                                    <textarea name="cards" class="form-control" rows="12" placeholder="卡号1 密码1&#10;卡号2 密码2&#10;卡号3 密码3&#10;...（如果没有密码，可以只写卡号）" required></textarea>
                                </div>
                                
                                <!-- 格式示例 -->
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0">格式示例：</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="card-example">
                                            ABC123456789 PASSWORD123<br>
                                            DEF987654321 SECRET456<br>
                                            GHI555666777<br>
                                            JKL888999000 MYKEY789
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                说明：使用空格分隔卡号和密码，如果没有密码可以只写卡号
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> 批量添加卡密
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearForm()">
                                        <i class="fas fa-eraser"></i> 清空内容
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 卡密列表 -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">卡密列表</h5>
                            <span class="badge bg-secondary">
                                总计: <?php echo count($cards); ?> 个卡密
                                (未售: <?php echo count(array_filter($cards, function($c) { return $c['status'] == 0; })); ?>)
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($cards)): ?>
                            
                            <!-- 批量操作 -->
                            <div class="batch-actions">
                                <form method="POST" id="batchForm">
                                    <input type="hidden" name="action" value="batch_delete">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label" for="selectAll">
                                                全选未售卡密
                                            </label>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmBatchDelete()">
                                                <i class="fas fa-trash"></i> 批量删除选中
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" id="selectAllHeader">
                                            </th>
                                            <th>ID</th>
                                            <th>卡号</th>
                                            <th>密码</th>
                                            <th>状态</th>
                                            <th>添加时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cards as $card): ?>
                                        <tr class="<?php echo $card['status'] ? 'table-success' : ''; ?>">
                                            <td>
                                                <?php if (!$card['status']): ?>
                                                <input type="checkbox" class="card-checkbox" name="card_ids[]" value="<?php echo $card['id']; ?>">
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $card['id']; ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($card['card_number']); ?></code>
                                            </td>
                                            <td>
                                                <?php if ($card['card_password']): ?>
                                                <code><?php echo htmlspecialchars($card['card_password']); ?></code>
                                                <?php else: ?>
                                                <span class="text-muted">无密码</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $card['status'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $card['status'] ? '已售' : '未售'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('Y-m-d H:i', strtotime($card['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if (!$card['status']): ?>
                                                <a href="cards.php?delete_card=<?php echo $card['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('确定删除这个卡密吗？此操作不可恢复！')">
                                                    <i class="fas fa-trash"></i> 删除
                                                </a>
                                                <?php else: ?>
                                                <span class="text-muted">已售出</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-credit-card fa-3x mb-3"></i>
                                <p>暂无卡密数据</p>
                                <p class="small">请在上方添加卡密</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($product_id && !$product): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 商品不存在或已被删除
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-credit-card fa-3x mb-3"></i>
                        <h5>请选择商品管理卡密</h5>
                        <p>从上方下拉菜单中选择一个商品来管理其卡密</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 清空表单
        function clearForm() {
            document.querySelector('textarea[name="cards"]').value = '';
        }
        
        // 全选功能
        document.getElementById('selectAllHeader').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.card-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.card-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            document.getElementById('selectAllHeader').checked = this.checked;
        });
        
        // 批量删除确认
        function confirmBatchDelete() {
            const selectedCards = document.querySelectorAll('.card-checkbox:checked');
            if (selectedCards.length === 0) {
                alert('请先选择要删除的卡密！');
                return;
            }
            
            if (confirm(`确定要删除选中的 ${selectedCards.length} 个卡密吗？此操作不可恢复！`)) {
                document.getElementById('batchForm').submit();
            }
        }
        
        // 单个复选框改变时更新全选状态
        document.querySelectorAll('.card-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allCheckboxes = document.querySelectorAll('.card-checkbox');
                const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                document.getElementById('selectAll').checked = allChecked;
                document.getElementById('selectAllHeader').checked = allChecked;
            });
        });
    </script>
</body>
</html>