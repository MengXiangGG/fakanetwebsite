<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

// 搜索参数
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : '';
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : '';

// 取消订单功能
if (isset($_GET['cancel'])) {
    $order_id = intval($_GET['cancel']);
    
    try {
        // 获取订单信息
        $stmt = $pdo->prepare("SELECT o.*, c.id as card_id FROM orders o LEFT JOIN cards c ON o.card_id = c.id WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // 开始事务
            $pdo->beginTransaction();
            
            // 如果订单已分配卡密，恢复卡密状态
            if ($order['card_id']) {
                $stmt = $pdo->prepare("UPDATE cards SET status = 0 WHERE id = ?");
                $stmt->execute([$order['card_id']]);
            }
            
            // 恢复商品库存
            if ($order['product_id']) {
                $stmt = $pdo->prepare("UPDATE products SET stock = stock + 1 WHERE id = ?");
                $stmt->execute([$order['product_id']]);
            }
            
            // 更新订单状态为已取消
            $stmt = $pdo->prepare("UPDATE orders SET status = 3 WHERE id = ?");
            $stmt->execute([$order_id]);
            
            // 提交事务
            $pdo->commit();
            
            $_SESSION['success'] = '订单取消成功！';
        } else {
            $_SESSION['error'] = '订单不存在！';
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = '取消订单失败：' . $e->getMessage();
    }
    
    header('Location: orders.php');
    exit;
}

// 获取所有分类
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC")->fetchAll();

// 构建查询条件
$where_conditions = [];
$params = [];

if (!empty($search_keyword)) {
    if ($search_type === 'order_no') {
        $where_conditions[] = "o.order_no LIKE ?";
        $params[] = "%{$search_keyword}%";
    } elseif ($search_type === 'contact') {
        $where_conditions[] = "o.contact_info LIKE ?";
        $params[] = "%{$search_keyword}%";
    }
}

if (!empty($category_id)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// 获取订单列表 - 显示正确的金额和数量
$sql = "
    SELECT o.*, p.name as product_name, p.category_id, c.name as category_name
    FROM orders o 
    LEFT JOIN products p ON o.product_id = p.id 
    LEFT JOIN categories c ON p.category_id = c.id
    {$where_sql}
    ORDER BY o.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单管理 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-status-0 { background-color: #fff3cd; } /* 待支付 */
        .order-status-1 { background-color: #d1edff; } /* 已支付 */
        .order-status-2 { background-color: #d4edda; } /* 已完成 */
        .order-status-3 { background-color: #f8d7da; } /* 已取消 */
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
                    <h2>订单管理</h2>
                    
                    <!-- 显示消息 -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- 搜索表单 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">订单搜索</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <select name="search_type" class="form-select">
                                        <option value="">搜索类型</option>
                                        <option value="order_no" <?php echo $search_type === 'order_no' ? 'selected' : ''; ?>>订单号</option>
                                        <option value="contact" <?php echo $search_type === 'contact' ? 'selected' : ''; ?>>联系方式</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="search_keyword" class="form-control" placeholder="请输入搜索关键词..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                                </div>
                                <div class="col-md-2">
                                    <select name="category_id" class="form-select">
                                        <option value="">所有分类</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">搜索</button>
                                </div>
                                <div class="col-md-3">
                                    <?php if (!empty($search_keyword) || !empty($category_id)): ?>
                                    <a href="orders.php" class="btn btn-outline-secondary w-100">清除搜索</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                            <?php if (!empty($search_keyword) || !empty($category_id)): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    搜索条件: 
                                    <?php if (!empty($search_keyword)): ?>
                                    <?php echo $search_type === 'order_no' ? '订单号' : '联系方式'; ?> 包含 "<?php echo htmlspecialchars($search_keyword); ?>"
                                    <?php endif; ?>
                                    <?php if (!empty($category_id) && !empty($search_keyword)): ?> | <?php endif; ?>
                                    <?php if (!empty($category_id)): ?>
                                    <?php 
                                    $selected_category = '';
                                    foreach ($categories as $cat) {
                                        if ($cat['id'] == $category_id) {
                                            $selected_category = $cat['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    分类: <?php echo htmlspecialchars($selected_category); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 订单统计 -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo count(array_filter($orders, function($o) { return $o['status'] == 0; })); ?></h4>
                                            <small>待支付</small>
                                        </div>
                                        <i class="fas fa-clock fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo count(array_filter($orders, function($o) { return $o['status'] == 1; })); ?></h4>
                                            <small>已支付</small>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo count(array_filter($orders, function($o) { return $o['status'] == 2; })); ?></h4>
                                            <small>已完成</small>
                                        </div>
                                        <i class="fas fa-flag fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo count(array_filter($orders, function($o) { return $o['status'] == 3; })); ?></h4>
                                            <small>已取消</small>
                                        </div>
                                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 订单列表 -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                订单列表
                                <?php if (!empty($search_keyword) || !empty($category_id)): ?>
                                <small class="text-muted fs-6">
                                    - 搜索到 <?php echo count($orders); ?> 条记录
                                </small>
                                <?php endif; ?>
                            </h5>
                            <div>
                                <a href="orders.php" class="btn btn-sm btn-outline-primary">全部</a>
                                <a href="orders.php?status=0" class="btn btn-sm btn-warning">待支付</a>
                                <a href="orders.php?status=1" class="btn btn-sm btn-success">已支付</a>
                                <a href="orders.php?status=3" class="btn btn-sm btn-danger">已取消</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($search_keyword) || !empty($category_id)): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-search me-2"></i>
                                搜索条件: 
                                <?php if (!empty($search_keyword)): ?>
                                <strong><?php echo $search_type === 'order_no' ? '订单号' : '联系方式'; ?></strong> 
                                包含 "<strong><?php echo htmlspecialchars($search_keyword); ?></strong>"
                                <?php endif; ?>
                                <?php if (!empty($category_id) && !empty($search_keyword)): ?> | <?php endif; ?>
                                <?php if (!empty($category_id)): ?>
                                <strong>分类</strong>: 
                                <?php 
                                $selected_category = '';
                                foreach ($categories as $cat) {
                                    if ($cat['id'] == $category_id) {
                                        $selected_category = $cat['name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($selected_category);
                                ?>
                                <?php endif; ?>
                                - 共找到 <strong><?php echo count($orders); ?></strong> 条记录
                                <a href="orders.php" class="btn btn-sm btn-outline-secondary ms-2">清除搜索</a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>订单号</th>
                                            <th>商品名称</th>
                                            <th>分类</th>
                                            <th>金额</th>
                                            <th>数量</th>
                                            <th>联系方式</th>
                                            <th>卡密</th>
                                            <th>状态</th>
                                            <th>支付时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr class="order-status-<?php echo $order['status']; ?>">
                                            <td>
                                                <small><?php echo $order['order_no']; ?></small>
                                                <br>
                                                <small class="text-muted"><?php echo $order['created_at']; ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($order['category_name']); ?></span>
                                            </td>
                                            <td class="fw-bold text-success">
                                                <?php 
                                                // 显示实际支付金额，而不是单价
                                                echo '¥' . number_format($order['final_amount'], 2); 
                                                ?>
                                                <?php if ($order['quantity'] > 1): ?>
                                                <br>
                                                <small class="text-muted">
                                                    (<?php echo $order['quantity']; ?>件 × ¥<?php echo number_format($order['price'], 2); ?>)
                                                    <?php if ($order['discount_amount'] > 0): ?>
                                                    -¥<?php echo number_format($order['discount_amount'], 2); ?>优惠
                                                    <?php endif; ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $order['quantity']; ?> 件</span>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['contact_info']); ?></td>
                                            <td>
    <?php 
    // 获取订单的所有卡密
    $cards_stmt = $pdo->prepare("SELECT * FROM order_cards WHERE order_no = ?");
    $cards_stmt->execute([$order['order_no']]);
    $cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($cards)): 
        $card_count = count($cards);
    ?>
    <div>
        <small>共 <?php echo $card_count; ?> 个卡密</small>
        <div class="mt-1">
            <button class="btn btn-sm btn-outline-info" 
                    onclick="showCards('<?php echo $order['order_no']; ?>')">
                <i class="fas fa-key me-1"></i>查看并复制卡密
            </button>
        </div>
    </div>
    <?php else: ?>
    <span class="text-muted">未分配</span>
    <?php endif; ?>
</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch($order['status']) {
                                                        case 0: echo 'warning'; break;
                                                        case 1: echo 'success'; break;
                                                        case 2: echo 'info'; break;
                                                        case 3: echo 'danger'; break;
                                                    }
                                                ?>">
                                                    <?php 
                                                    switch($order['status']) {
                                                        case 0: echo '待支付'; break;
                                                        case 1: echo '已支付'; break;
                                                        case 2: echo '已完成'; break;
                                                        case 3: echo '已取消'; break;
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo $order['paid_at'] ?: '-'; ?></td>
                                            <td>
                                                <?php if ($order['status'] == 0): ?>
                                                <a href="orders.php?cancel=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('确定要取消这个订单吗？此操作将恢复库存！')">
                                                    <i class="fas fa-times"></i> 取消订单
                                                </a>
                                                <?php elseif ($order['status'] == 1): ?>
                                                <span class="text-success">已支付</span>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <?php if (!empty($search_keyword) || !empty($category_id)): ?>
                                <i class="fas fa-search fa-3x mb-3"></i>
                                <h5>未找到匹配的订单</h5>
                                <p>
                                    <?php if (!empty($search_keyword)): ?>
                                    搜索条件: <strong><?php echo $search_type === 'order_no' ? '订单号' : '联系方式'; ?></strong> 
                                    包含 "<strong><?php echo htmlspecialchars($search_keyword); ?></strong>"
                                    <?php endif; ?>
                                    <?php if (!empty($category_id) && !empty($search_keyword)): ?> | <?php endif; ?>
                                    <?php if (!empty($category_id)): ?>
                                    <strong>分类</strong>: 
                                    <?php 
                                    $selected_category = '';
                                    foreach ($categories as $cat) {
                                        if ($cat['id'] == $category_id) {
                                            $selected_category = $cat['name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($selected_category);
                                    ?>
                                    <?php endif; ?>
                                </p>
                                <a href="orders.php" class="btn btn-primary">查看所有订单</a>
                                <?php else: ?>
                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                <h5>暂无订单记录</h5>
                                <p>还没有任何订单数据</p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 卡密详情模态框 -->
    <div class="modal fade" id="cardsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">卡密详情 - <span id="modalOrderNo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cardsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 显示卡密详情
        function showCards(orderNo) {
            document.getElementById('modalOrderNo').textContent = orderNo;
            
            // 加载卡密信息
            fetch('get_order_cards.php?order_no=' + orderNo)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('cardsContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('cardsModal')).show();
                })
                .catch(error => {
                    document.getElementById('cardsContent').innerHTML = '<div class="alert alert-danger">加载失败</div>';
                    new bootstrap.Modal(document.getElementById('cardsModal')).show();
                });
        }
    </script>
</body>
</html>