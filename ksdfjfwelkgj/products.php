<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

// 处理商品操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] == 'add') {
        $category_id = intval($_POST['category_id']);
        $name = safe_input($_POST['name']);
        $description = safe_input($_POST['description']);
        $price = floatval($_POST['price']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $description, $price, $status]);
            
            $_SESSION['success'] = '商品添加成功！请记得添加卡密';
            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            $error = '添加商品失败: ' . $e->getMessage();
        }
    }
    elseif ($_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $category_id = intval($_POST['category_id']);
        $name = safe_input($_POST['name']);
        $description = safe_input($_POST['description']);
        $price = floatval($_POST['price']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE products SET category_id=?, name=?, description=?, price=?, status=? WHERE id=?");
            $stmt->execute([$category_id, $name, $description, $price, $status, $id]);
            
            $_SESSION['success'] = '商品更新成功！';
            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            $error = '更新商品失败: ' . $e->getMessage();
        }
    }
}

// 删除商品
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        // 检查是否有订单关联
        $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE product_id = ?");
        $stmt->execute([$id]);
        $order_count = $stmt->fetch(PDO::FETCH_ASSOC)['order_count'];
        
        if ($order_count > 0) {
            $_SESSION['error'] = '该商品已有订单记录，无法删除！';
        } else {
            // 删除商品相关的卡密
            $stmt = $pdo->prepare("DELETE FROM cards WHERE product_id = ?");
            $stmt->execute([$id]);
            
            // 删除商品
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = '商品删除成功！';
        }
        
        header('Location: products.php');
        exit;
    } catch (PDOException $e) {
        $error = '删除商品失败: ' . $e->getMessage();
    }
}

// 获取筛选参数
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : '';
$search_keyword = isset($_GET['search']) ? safe_input($_GET['search']) : '';

// 获取分类列表
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC")->fetchAll();

// 构建商品查询条件
$where_conditions = [];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search_keyword)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$search_keyword}%";
    $params[] = "%{$search_keyword}%";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// 获取商品列表（包含库存信息）
$sql = "
    SELECT p.*, c.name as category_name,
           (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND status = 0) as stock
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    {$where_sql}
    ORDER BY p.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品管理 - <?php echo SITE_NAME; ?>后台</title>
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
                    <h2>商品管理</h2>
                    
                    <!-- 显示消息 -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- 搜索和筛选表单 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">搜索和筛选</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <select name="category_id" class="form-select">
                                        <option value="">所有分类</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" placeholder="搜索商品名称或描述..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>搜索
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <?php if (!empty($category_filter) || !empty($search_keyword)): ?>
                                    <a href="products.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-times me-2"></i>清除筛选
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                            
                            <?php if (!empty($category_filter) || !empty($search_keyword)): ?>
                            <div class="mt-3">
                                <small class="text-muted">
                                    筛选条件: 
                                    <?php if (!empty($category_filter)): 
                                        $selected_category = '';
                                        foreach ($categories as $cat) {
                                            if ($cat['id'] == $category_filter) {
                                                $selected_category = $cat['name'];
                                                break;
                                            }
                                        }
                                    ?>
                                    <strong>分类</strong>: <?php echo htmlspecialchars($selected_category); ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($category_filter) && !empty($search_keyword)): ?> | <?php endif; ?>
                                    
                                    <?php if (!empty($search_keyword)): ?>
                                    <strong>关键词</strong>: "<?php echo htmlspecialchars($search_keyword); ?>"
                                    <?php endif; ?>
                                    
                                    - 共找到 <strong><?php echo count($products); ?></strong> 个商品
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 添加商品表单 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">添加新商品</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                    <div class="col-md-4">
                                        <select name="category_id" class="form-control" required>
                                            <option value="">选择分类</option>
                                            <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="name" class="form-control" placeholder="商品名称" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="price" class="form-control" placeholder="价格" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">添加商品</button>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <textarea name="description" class="form-control" placeholder="商品描述" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="status" id="status" checked>
                                            <label class="form-check-label" for="status">
                                                立即上架
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 商品列表 -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                商品列表
                                <?php if (!empty($category_filter) || !empty($search_keyword)): ?>
                                <small class="text-muted fs-6">
                                    - 筛选到 <?php echo count($products); ?> 个商品
                                </small>
                                <?php endif; ?>
                            </h5>
                            <div>
                                <span class="badge bg-primary">总计: <?php echo count($products); ?> 个商品</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($products)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>商品名称</th>
                                            <th>分类</th>
                                            <th>价格</th>
                                            <th>库存</th>
                                            <th>状态</th>
                                            <th>添加时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo $product['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <?php if ($product['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($product['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                            </td>
                                            <td>¥<?php echo number_format($product['price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $product['stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['status'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $product['status'] ? '上架' : '下架'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                            <td>
                                                <a href="cards.php?product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-credit-card"></i> 管理卡密
                                                </a>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal"
                                                        data-id="<?php echo $product['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                                        data-category="<?php echo $product['category_id']; ?>"
                                                        data-price="<?php echo $product['price']; ?>"
                                                        data-status="<?php echo $product['status']; ?>">
                                                    <i class="fas fa-edit"></i> 编辑
                                                </button>
                                                <a href="products.php?delete=<?php echo $product['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('确定删除这个商品吗？此操作将同时删除所有相关卡密！')">
                                                    <i class="fas fa-trash"></i> 删除
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <?php if (!empty($category_filter) || !empty($search_keyword)): ?>
                                <i class="fas fa-search fa-3x mb-3"></i>
                                <h5>未找到匹配的商品</h5>
                                <p>
                                    <?php if (!empty($category_filter)): 
                                        $selected_category = '';
                                        foreach ($categories as $cat) {
                                            if ($cat['id'] == $category_filter) {
                                                $selected_category = $cat['name'];
                                                break;
                                            }
                                        }
                                    ?>
                                    分类: <strong><?php echo htmlspecialchars($selected_category); ?></strong>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($category_filter) && !empty($search_keyword)): ?> | <?php endif; ?>
                                    
                                    <?php if (!empty($search_keyword)): ?>
                                    关键词: "<strong><?php echo htmlspecialchars($search_keyword); ?></strong>"
                                    <?php endif; ?>
                                </p>
                                <a href="products.php" class="btn btn-primary">查看所有商品</a>
                                <?php else: ?>
                                <i class="fas fa-box fa-3x mb-3"></i>
                                <h5>暂无商品</h5>
                                <p>请在上方添加商品</p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 编辑商品模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑商品</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">商品名称</label>
                                    <input type="text" name="name" id="editName" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">分类</label>
                                    <select name="category_id" id="editCategory" class="form-control" required>
                                        <option value="">选择分类</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">价格</label>
                                    <input type="number" name="price" id="editPrice" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">商品描述</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="status" id="editStatus">
                                <label class="form-check-label" for="editStatus">
                                    上架商品
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存更改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 编辑模态框数据填充
        var editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('editId').value = button.getAttribute('data-id');
            document.getElementById('editName').value = button.getAttribute('data-name');
            document.getElementById('editDescription').value = button.getAttribute('data-description');
            document.getElementById('editCategory').value = button.getAttribute('data-category');
            document.getElementById('editPrice').value = button.getAttribute('data-price');
            document.getElementById('editStatus').checked = button.getAttribute('data-status') === '1';
        });
    </script>
</body>
</html>