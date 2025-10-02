<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

// 添加分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] == 'add') {
    $name = safe_input($_POST['name']);
    $description = safe_input($_POST['description']);
    $sort_order = intval($_POST['sort_order']);
    
    // 生成随机标识符
    $random_slug = generateRandomSlug();
    
    $stmt = $pdo->prepare("INSERT INTO categories (name, description, sort_order, random_slug) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $description, $sort_order, $random_slug])) {
        $_SESSION['success'] = '分类添加成功！';
        header('Location: categories.php?success=1');
        exit;
    } else {
        $_SESSION['error'] = '分类添加失败！';
    }
}

// 编辑分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] == 'edit') {
    $id = intval($_POST['id']);
    $name = safe_input($_POST['name']);
    $description = safe_input($_POST['description']);
    $sort_order = intval($_POST['sort_order']);
    $status = intval($_POST['status']);
    
    $stmt = $pdo->prepare("UPDATE categories SET name=?, description=?, sort_order=?, status=? WHERE id=?");
    if ($stmt->execute([$name, $description, $sort_order, $status, $id])) {
        $_SESSION['success'] = '分类更新成功！';
        header('Location: categories.php?success=1');
        exit;
    } else {
        $_SESSION['error'] = '分类更新失败！';
    }
}

// 重新生成随机链接
if (isset($_GET['regenerate'])) {
    $id = intval($_GET['regenerate']);
    $new_slug = generateRandomSlug();
    
    $stmt = $pdo->prepare("UPDATE categories SET random_slug = ? WHERE id = ?");
    if ($stmt->execute([$new_slug, $id])) {
        $_SESSION['success'] = '分类链接已重新生成！';
    } else {
        $_SESSION['error'] = '重新生成链接失败！';
    }
    header('Location: categories.php');
    exit;
}

// 删除分类
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // 检查分类下是否有商品
    $stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['product_count'] > 0) {
        $_SESSION['error'] = '该分类下还有商品，无法删除！';
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = '分类删除成功！';
        } else {
            $_SESSION['error'] = '分类删除失败！';
        }
    }
    
    header('Location: categories.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
$sales_stats = getCategorySalesStats();
$sales_summary = getCategorySalesSummary();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sales-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .today-sales { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .total-sales { background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%); }
        .sales-badge { 
            font-size: 0.8rem; 
            padding: 4px 8px;
            margin-left: 5px;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 5px 0;
        }
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .trend-up { color: #28a745; }
        .trend-down { color: #dc3545; }
        .trend-neutral { color: #6c757d; }
        .sales-trend {
            font-size: 0.8rem;
            margin-left: 5px;
        }
        .balance-highlight {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: bold;
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
                    <h2>商品分类管理</h2>
                    
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

                    <!-- 销售统计汇总 -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="sales-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number"><?php echo $sales_summary['category_count'] ?? 0; ?></div>
                                        <div class="stat-label">分类数量</div>
                                    </div>
                                    <i class="fas fa-list fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sales-card today-sales">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number">¥<?php echo number_format($sales_summary['total_today_sales'] ?? 0, 2); ?></div>
                                        <div class="stat-label">今日销售额</div>
                                        <small>订单: <?php echo $sales_summary['total_today_orders'] ?? 0; ?> 笔</small>
                                    </div>
                                    <i class="fas fa-sun fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sales-card total-sales">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number">¥<?php echo number_format($sales_summary['total_all_sales'] ?? 0, 2); ?></div>
                                        <div class="stat-label">总销售额</div>
                                        <small>订单: <?php echo $sales_summary['total_all_orders'] ?? 0; ?> 笔</small>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sales-card" style="background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number"><?php 
                                            $enabled_count = 0;
                                            foreach ($categories as $cat) {
                                                if ($cat['status'] == 1) $enabled_count++;
                                            }
                                            echo $enabled_count;
                                        ?></div>
                                        <div class="stat-label">启用分类</div>
                                        <small>总数: <?php echo count($categories); ?></small>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 费率信息 -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-percentage fa-2x me-3 text-info"></i>
                            <div>
                                <h6 class="mb-1">费率说明</h6>
                                <p class="mb-0">
                                    所有收入将自动扣除 <strong class="text-danger"><?php echo (TRANSACTION_FEE_RATE * 100); ?>%</strong> 的交易费率，
                                    分类余额显示的是扣除费率后的实际可提现金额。
                                    <br><small class="text-muted">例如：订单金额 100元 → 手续费 <?php echo number_format(100 * TRANSACTION_FEE_RATE, 2); ?>元 → 实际到账 <?php echo number_format(100 * (1 - TRANSACTION_FEE_RATE), 2); ?>元</small>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 添加分类表单 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">添加新分类</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                    <div class="col-md-4">
                                        <input type="text" name="name" class="form-control" placeholder="分类名称" required>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="description" class="form-control" placeholder="分类描述">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="sort_order" class="form-control" placeholder="排序" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">添加分类</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 分类列表 -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">分类列表</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>分类名称</th>
                                            <th>描述</th>
                                            <th>销售统计</th>
                                            <th>分类余额</th>
                                            <th>排序</th>
                                            <th>状态</th>
                                            <th>分享链接</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // 创建销售统计映射
                                        $stats_map = [];
                                        foreach ($sales_stats as $stat) {
                                            $stats_map[$stat['id']] = $stat;
                                        }
                                        
                                        foreach ($categories as $category): 
                                            $stat = $stats_map[$category['id']] ?? [
                                                'today_sales' => 0,
                                                'total_sales' => 0,
                                                'today_orders' => 0,
                                                'total_orders' => 0
                                            ];
                                            
                                            // 获取销售趋势
                                            $trend = getCategorySalesTrend($category['id'], 2);
                                            $yesterday_sales = isset($trend[1]) ? $trend[1]['daily_sales'] : 0;
                                            $today_sales = $stat['today_sales'];
                                            
                                            // 计算趋势
                                            $trend_class = 'trend-neutral';
                                            $trend_icon = '';
                                            if ($yesterday_sales > 0) {
                                                if ($today_sales > $yesterday_sales) {
                                                    $trend_class = 'trend-up';
                                                    $trend_icon = '<i class="fas fa-arrow-up"></i>';
                                                } elseif ($today_sales < $yesterday_sales) {
                                                    $trend_class = 'trend-down';
                                                    $trend_icon = '<i class="fas fa-arrow-down"></i>';
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                                            <td>
                                                <div class="sales-info">
                                                    <div class="mb-1">
                                                        <small class="text-muted">今日:</small>
                                                        <strong class="text-success">¥<?php echo number_format($stat['today_sales'], 2); ?></strong>
                                                        <span class="sales-trend <?php echo $trend_class; ?>">
                                                            <?php echo $trend_icon; ?>
                                                        </span>
                                                        <span class="badge bg-secondary sales-badge">
                                                            <?php echo $stat['today_orders']; ?>单
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">累计:</small>
                                                        <strong class="text-warning">¥<?php echo number_format($stat['total_sales'], 2); ?></strong>
                                                        <span class="badge bg-dark sales-badge">
                                                            <?php echo $stat['total_orders']; ?>单
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $balance = getCategoryBalance($category['id']);
                                                if ($balance > 0): ?>
                                                <div class="balance-highlight">
                                                    ¥<?php echo number_format($balance, 2); ?>
                                                </div>
                                                <small class="text-success">可提现</small>
                                                <?php else: ?>
                                                <span class="text-muted">¥<?php echo number_format($balance, 2); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $category['sort_order']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $category['status'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $category['status'] ? '启用' : '禁用'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" 
                                                           value="<?php echo SITE_URL; ?>category.php?slug=<?php echo $category['random_slug']; ?>" 
                                                           readonly id="shareLink<?php echo $category['id']; ?>">
                                                    <button class="btn btn-outline-secondary" type="button" 
                                                            onclick="copyShareLink(<?php echo $category['id']; ?>)">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" type="button" 
                                                            onclick="regenerateLink(<?php echo $category['id']; ?>)">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">点击复制或重新生成链接</small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                                        data-bs-target="#salesModal"
                                                        data-id="<?php echo $category['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                    <i class="fas fa-chart-bar"></i> 详情
                                                </button>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                        data-bs-target="#editModal" 
                                                        data-id="<?php echo $category['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                        data-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                        data-sort="<?php echo $category['sort_order']; ?>"
                                                        data-status="<?php echo $category['status']; ?>">
                                                    <i class="fas fa-edit"></i> 编辑
                                                </button>
                                                <a href="categories.php?delete=<?php echo $category['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('确定删除这个分类吗？')">
                                                    <i class="fas fa-trash"></i> 删除
                                                </a>
                                            </td>
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

    <!-- 编辑模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑分类</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">分类名称</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">分类描述</label>
                            <input type="text" name="description" id="editDescription" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">排序</label>
                            <input type="number" name="sort_order" id="editSort" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">状态</label>
                            <select name="status" id="editStatus" class="form-control">
                                <option value="1">启用</option>
                                <option value="0">禁用</option>
                            </select>
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

    <!-- 销售详情模态框 -->
    <div class="modal fade" id="salesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">销售详情 - <span id="salesCategoryName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="salesDetailContent">
                        <!-- 内容通过AJAX加载 -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 复制分享链接
        function copyShareLink(categoryId) {
            const shareLink = document.getElementById('shareLink' + categoryId);
            shareLink.select();
            document.execCommand('copy');
            
            // 显示复制成功提示
            const toast = document.createElement('div');
            toast.className = 'alert alert-success position-fixed';
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 200px;';
            toast.innerHTML = '<i class="fas fa-check-circle me-2"></i>链接已复制到剪贴板！';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // 重新生成链接
        function regenerateLink(categoryId) {
            if (confirm('确定要重新生成这个分类的链接吗？旧的链接将失效！')) {
                window.location.href = 'categories.php?regenerate=' + categoryId;
            }
        }

        // 编辑模态框数据填充
        var editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('editId').value = button.getAttribute('data-id');
            document.getElementById('editName').value = button.getAttribute('data-name');
            document.getElementById('editDescription').value = button.getAttribute('data-description');
            document.getElementById('editSort').value = button.getAttribute('data-sort');
            document.getElementById('editStatus').value = button.getAttribute('data-status');
        });

        // 销售详情模态框
        var salesModal = document.getElementById('salesModal');
        salesModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var categoryId = button.getAttribute('data-id');
            var categoryName = button.getAttribute('data-name');
            
            document.getElementById('salesCategoryName').textContent = categoryName;
            
            // 加载销售详情
            fetch('category_sales_detail.php?id=' + categoryId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('salesDetailContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('salesDetailContent').innerHTML = '<div class="alert alert-danger">加载失败</div>';
                });
        });
    </script>
</body>
</html>