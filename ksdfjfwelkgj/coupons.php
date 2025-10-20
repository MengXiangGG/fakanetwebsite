<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

// 添加优惠券
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] == 'add') {
    $code = safe_input($_POST['code']);
    $name = safe_input($_POST['name']);
    $type = $_POST['type'];
    $value = floatval($_POST['value']);
    $min_amount = floatval($_POST['min_amount']);
    $max_discount = floatval($_POST['max_discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $status = isset($_POST['status']) ? 1 : 0;
    $applicable_categories = isset($_POST['categories']) ? implode(',', $_POST['categories']) : null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, name, type, value, min_amount, max_discount, usage_limit, start_date, end_date, status, applicable_categories) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $type, $value, $min_amount, $max_discount, $usage_limit, $start_date, $end_date, $status, $applicable_categories]);
        
        $_SESSION['success'] = '优惠券添加成功！';
        header('Location: coupons.php');
        exit;
    } catch (PDOException $e) {
        $error = '添加优惠券失败: ' . $e->getMessage();
    }
}

// 编辑优惠券
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] == 'edit') {
    $id = intval($_POST['id']);
    $code = safe_input($_POST['code']);
    $name = safe_input($_POST['name']);
    $type = $_POST['type'];
    $value = floatval($_POST['value']);
    $min_amount = floatval($_POST['min_amount']);
    $max_discount = floatval($_POST['max_discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $status = isset($_POST['status']) ? 1 : 0;
    $applicable_categories = isset($_POST['categories']) ? implode(',', $_POST['categories']) : null;
    
    try {
        $stmt = $pdo->prepare("UPDATE coupons SET code=?, name=?, type=?, value=?, min_amount=?, max_discount=?, usage_limit=?, start_date=?, end_date=?, status=?, applicable_categories=? WHERE id=?");
        $stmt->execute([$code, $name, $type, $value, $min_amount, $max_discount, $usage_limit, $start_date, $end_date, $status, $applicable_categories, $id]);
        
        $_SESSION['success'] = '优惠券更新成功！';
        header('Location: coupons.php');
        exit;
    } catch (PDOException $e) {
        $error = '更新优惠券失败: ' . $e->getMessage();
    }
}

// 删除优惠券
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        // 检查是否有使用记录
        $stmt = $pdo->prepare("SELECT COUNT(*) as usage_count FROM coupon_usage WHERE coupon_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['usage_count'] > 0) {
            $_SESSION['error'] = '该优惠券已有使用记录，无法删除！';
        } else {
            $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = '优惠券删除成功！';
        }
        
        header('Location: coupons.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = '删除优惠券失败: ' . $e->getMessage();
        header('Location: coupons.php');
        exit;
    }
}

// 获取优惠券列表
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();

// 获取分类列表
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1")->fetchAll();

// 获取优惠券使用统计
$coupon_stats = [];
foreach ($coupons as $coupon) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as usage_count, SUM(discount_amount) as total_discount FROM coupon_usage WHERE coupon_id = ?");
    $stmt->execute([$coupon['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $coupon_stats[$coupon['id']] = $stats;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>优惠券管理 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .coupon-card {
            border: none;
            border-radius: 10px;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .fixed-coupon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .percent-coupon { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .coupon-code {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
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
        .expired {
            opacity: 0.6;
            position: relative;
        }
        .expired::after {
            content: "已过期";
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
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
                    <h2>优惠券管理</h2>
                    
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

                    <!-- 统计卡片 -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="coupon-card fixed-coupon">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number"><?php echo count($coupons); ?></div>
                                        <div class="stat-label">优惠券总数</div>
                                    </div>
                                    <i class="fas fa-ticket-alt fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="coupon-card percent-coupon">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php
                                        $active_count = 0;
                                        foreach ($coupons as $coupon) {
                                            if ($coupon['status'] == 1 && 
                                                (!$coupon['start_date'] || $coupon['start_date'] <= date('Y-m-d')) &&
                                                (!$coupon['end_date'] || $coupon['end_date'] >= date('Y-m-d')) &&
                                                (!$coupon['usage_limit'] || $coupon['used_count'] < $coupon['usage_limit'])) {
                                                $active_count++;
                                            }
                                        }
                                        ?>
                                        <div class="stat-number"><?php echo $active_count; ?></div>
                                        <div class="stat-label">可用优惠券</div>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="coupon-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php
                                        $total_usage = 0;
                                        $total_discount = 0;
                                        foreach ($coupon_stats as $stats) {
                                            $total_usage += $stats['usage_count'];
                                            $total_discount += $stats['total_discount'] ?: 0;
                                        }
                                        ?>
                                        <div class="stat-number"><?php echo $total_usage; ?></div>
                                        <div class="stat-label">总使用次数</div>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="coupon-card" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number">¥<?php echo number_format($total_discount, 2); ?></div>
                                        <div class="stat-label">总优惠金额</div>
                                    </div>
                                    <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 添加优惠券表单 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">添加新优惠券</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" name="code" class="form-control" placeholder="优惠券代码" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="name" class="form-control" placeholder="优惠券名称" required>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="type" class="form-control" required>
                                            <option value="fixed">固定金额</option>
                                            <option value="percent">百分比</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="value" class="form-control" placeholder="优惠值" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">添加优惠券</button>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-2">
                                        <input type="number" name="min_amount" class="form-control" placeholder="最低金额" step="0.01" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="max_discount" class="form-control" placeholder="最大折扣" step="0.01" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="usage_limit" class="form-control" placeholder="使用限制" min="0" value="0">
                                        <small class="form-text text-muted">0表示无限制</small>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="start_date" class="form-control" placeholder="开始日期">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="end_date" class="form-control" placeholder="结束日期">
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="status" id="status" checked>
                                            <label class="form-check-label" for="status">
                                                立即启用
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <label class="form-label">适用分类（可选，不选则适用于所有分类）</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($categories as $category): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="cat_<?php echo $category['id']; ?>">
                                                <label class="form-check-label" for="cat_<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 优惠券列表 -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">优惠券列表</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>优惠券代码</th>
                                            <th>名称</th>
                                            <th>类型</th>
                                            <th>优惠值</th>
                                            <th>使用条件</th>
                                            <th>使用统计</th>
                                            <th>有效期</th>
                                            <th>状态</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coupons as $coupon): 
                                            $is_expired = $coupon['end_date'] && $coupon['end_date'] < date('Y-m-d');
                                            $is_limited = $coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit'];
                                            $stats = $coupon_stats[$coupon['id']] ?? ['usage_count' => 0, 'total_discount' => 0];
                                        ?>
                                        <tr class="<?php echo $is_expired ? 'expired' : ''; ?>">
                                            <td><?php echo $coupon['id']; ?></td>
                                            <td>
                                                <span class="coupon-code"><?php echo $coupon['code']; ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($coupon['name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $coupon['type'] == 'fixed' ? 'primary' : 'success'; ?>">
                                                    <?php echo $coupon['type'] == 'fixed' ? '固定金额' : '百分比'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($coupon['type'] == 'fixed'): ?>
                                                ¥<?php echo number_format($coupon['value'], 2); ?>
                                                <?php else: ?>
                                                <?php echo $coupon['value']; ?>%
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    最低金额: ¥<?php echo number_format($coupon['min_amount'], 2); ?><br>
                                                    <?php if ($coupon['type'] == 'percent'): ?>
                                                    最大折扣: ¥<?php echo number_format($coupon['max_discount'], 2); ?><br>
                                                    <?php endif; ?>
                                                    使用限制: <?php echo $coupon['usage_limit'] ?: '无限制'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    已使用: <?php echo $stats['usage_count']; ?> 次<br>
                                                    优惠金额: ¥<?php echo number_format($stats['total_discount'], 2); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo $coupon['start_date'] ?: '无限制'; ?> <br>
                                                    至 <?php echo $coupon['end_date'] ?: '无限制'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    if (!$coupon['status']) echo 'secondary';
                                                    elseif ($is_expired) echo 'warning';
                                                    elseif ($is_limited) echo 'info';
                                                    else echo 'success';
                                                ?>">
                                                    <?php 
                                                    if (!$coupon['status']) echo '禁用';
                                                    elseif ($is_expired) echo '已过期';
                                                    elseif ($is_limited) echo '已用完';
                                                    else echo '可用';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                        data-bs-target="#editModal"
                                                        data-id="<?php echo $coupon['id']; ?>"
                                                        data-code="<?php echo htmlspecialchars($coupon['code']); ?>"
                                                        data-name="<?php echo htmlspecialchars($coupon['name']); ?>"
                                                        data-type="<?php echo $coupon['type']; ?>"
                                                        data-value="<?php echo $coupon['value']; ?>"
                                                        data-min-amount="<?php echo $coupon['min_amount']; ?>"
                                                        data-max-discount="<?php echo $coupon['max_discount']; ?>"
                                                        data-usage-limit="<?php echo $coupon['usage_limit']; ?>"
                                                        data-start-date="<?php echo $coupon['start_date']; ?>"
                                                        data-end-date="<?php echo $coupon['end_date']; ?>"
                                                        data-status="<?php echo $coupon['status']; ?>"
                                                        data-categories="<?php echo $coupon['applicable_categories']; ?>">
                                                    <i class="fas fa-edit"></i> 编辑
                                                </button>
                                                <a href="coupons.php?delete=<?php echo $coupon['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('确定删除这个优惠券吗？')">
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

    <!-- 编辑优惠券模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑优惠券</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">优惠券代码</label>
                                    <input type="text" name="code" id="editCode" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">优惠券名称</label>
                                    <input type="text" name="name" id="editName" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">优惠类型</label>
                                    <select name="type" id="editType" class="form-control" required>
                                        <option value="fixed">固定金额</option>
                                        <option value="percent">百分比</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">优惠值</label>
                                    <input type="number" name="value" id="editValue" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">最低金额</label>
                                    <input type="number" name="min_amount" id="editMinAmount" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">最大折扣（百分比时有效）</label>
                                    <input type="number" name="max_discount" id="editMaxDiscount" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">使用限制</label>
                                    <input type="number" name="usage_limit" id="editUsageLimit" class="form-control" min="0">
                                    <small class="form-text text-muted">0表示无限制</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">状态</label>
                                    <select name="status" id="editStatus" class="form-control">
                                        <option value="1">启用</option>
                                        <option value="0">禁用</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">开始日期</label>
                                    <input type="date" name="start_date" id="editStartDate" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">结束日期</label>
                                    <input type="date" name="end_date" id="editEndDate" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">适用分类（可选，不选则适用于所有分类）</label>
                            <div class="d-flex flex-wrap gap-2" id="editCategoriesContainer">
                                <?php foreach ($categories as $category): ?>
                                <div class="form-check">
                                    <input class="form-check-input category-checkbox" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="edit_cat_<?php echo $category['id']; ?>">
                                    <label class="form-check-label" for="edit_cat_<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
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
            
            // 设置基本字段
            document.getElementById('editId').value = button.getAttribute('data-id');
            document.getElementById('editCode').value = button.getAttribute('data-code');
            document.getElementById('editName').value = button.getAttribute('data-name');
            document.getElementById('editType').value = button.getAttribute('data-type');
            document.getElementById('editValue').value = button.getAttribute('data-value');
            document.getElementById('editMinAmount').value = button.getAttribute('data-min-amount');
            document.getElementById('editMaxDiscount').value = button.getAttribute('data-max-discount');
            document.getElementById('editUsageLimit').value = button.getAttribute('data-usage-limit');
            document.getElementById('editStartDate').value = button.getAttribute('data-start-date');
            document.getElementById('editEndDate').value = button.getAttribute('data-end-date');
            document.getElementById('editStatus').value = button.getAttribute('data-status');
            
            // 清除所有分类选择
            var checkboxes = document.querySelectorAll('.category-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            
            // 设置适用的分类
            var categories = button.getAttribute('data-categories');
            if (categories) {
                var categoryArray = categories.split(',');
                categoryArray.forEach(function(categoryId) {
                    var checkbox = document.getElementById('edit_cat_' + categoryId);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        });
    </script>
</body>
</html>