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
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// 取消订单功能 - 更新为支持批量购买
if (isset($_GET['cancel'])) {
    $order_id = intval($_GET['cancel']);
    
    try {
        // 获取订单信息
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // 开始事务
            $pdo->beginTransaction();
            
            // 如果订单已分配卡密，恢复所有卡密状态
            $cards_stmt = $pdo->prepare("SELECT * FROM order_cards WHERE order_no = ?");
            $cards_stmt->execute([$order['order_no']]);
            $cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($cards as $card) {
                if ($card['card_id']) {
                    $stmt = $pdo->prepare("UPDATE cards SET status = 0 WHERE id = ?");
                    $stmt->execute([$card['card_id']]);
                }
            }
            
            // 删除订单卡密记录
            $stmt = $pdo->prepare("DELETE FROM order_cards WHERE order_no = ?");
            $stmt->execute([$order['order_no']]);
            
            // 如果订单已支付，需要退还分类余额（扣除费率后的金额）
            if ($order['status'] == 1 && $order['category_id']) {
                $net_amount = calculateNetAmount($order['final_amount']);
                $refund_amount = $net_amount['net_amount'];
                
                $stmt = $pdo->prepare("UPDATE categories SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$refund_amount, $order['category_id']]);
            }
            
            // 更新订单状态为已取消
            $stmt = $pdo->prepare("UPDATE orders SET status = 3 WHERE id = ?");
            $stmt->execute([$order_id]);
            
            // 提交事务
            $pdo->commit();
            
            $_SESSION['success'] = '订单取消成功！相关卡密已恢复，余额已退还。';
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

if ($status_filter !== '') {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
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

// 获取订单统计
$total_orders = count($orders);
$pending_orders = count(array_filter($orders, function($o) { return $o['status'] == 0; }));
$paid_orders = count(array_filter($orders, function($o) { return $o['status'] == 1; }));
$completed_orders = count(array_filter($orders, function($o) { return $o['status'] == 2; }));
$cancelled_orders = count(array_filter($orders, function($o) { return $o['status'] == 3; }));
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
        .filter-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
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
                                <div class="col-md-2">
                                    <input type="text" name="search_keyword" class="form-control" placeholder="搜索关键词..." value="<?php echo htmlspecialchars($search_keyword); ?>">
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
                                    <select name="status" class="form-select">
                                        <option value="">所有状态</option>
                                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>待支付</option>
                                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>已支付</option>
                                        <option value="2" <?php echo $status_filter === '2' ? 'selected' : ''; ?>>已完成</option>
                                        <option value="3" <?php echo $status_filter === '3' ? 'selected' : ''; ?>>已取消</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>搜索
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <?php if (!empty($search_keyword) || !empty($category_id) || $status_filter !== ''): ?>
                                    <a href="orders.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-times me-2"></i>清除搜索
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                            
                            <?php if (!empty($search_keyword) || !empty($category_id) || $status_filter !== ''): ?>
                            <div class="mt-3">
                                <small class="text-muted">
                                    搜索条件: 
                                    <?php if (!empty($search_keyword)): ?>
                                    <span class="badge bg-primary filter-badge">
                                        <?php echo $search_type === 'order_no' ? '订单号' : '联系方式'; ?>: "<?php echo htmlspecialchars($search_keyword); ?>"
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($category_id)): 
                                        $selected_category = '';
                                        foreach ($categories as $cat) {
                                            if ($cat['id'] == $category_id) {
                                                $selected_category = $cat['name'];
                                                break;
                                            }
                                        }
                                    ?>
                                    <span class="badge bg-info filter-badge">
                                        分类: <?php echo htmlspecialchars($selected_category); ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($status_filter !== ''): ?>
                                    <span class="badge bg-<?php 
                                        switch($status_filter) {
                                            case '0': echo 'warning'; break;
                                            case '1': echo 'success'; break;
                                            case '2': echo 'info'; break;
                                            case '3': echo 'danger'; break;
                                        }
                                    ?> filter-badge">
                                        状态: <?php 
                                        switch($status_filter) {
                                            case '0': echo '待支付'; break;
                                            case '1': echo '已支付'; break;
                                            case '2': echo '已完成'; break;
                                            case '3': echo '已取消'; break;
                                        }
                                        ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    - 共找到 <strong><?php echo $total_orders; ?></strong> 条记录
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
                                            <h4><?php echo $total_orders; ?></h4>
                                            <small>总订单数</small>
                                        </div>
                                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo $pending_orders; ?></h4>
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
                                            <h4><?php echo $paid_orders; ?></h4>
                                            <small>已支付</small>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo $cancelled_orders; ?></h4>
                                            <small>已取消</small>
                                        </div>
                                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 快捷状态筛选 -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="btn-group">
                                <a href="orders.php" class="btn btn-outline-primary <?php echo empty($search_keyword) && empty($category_id) && $status_filter === '' ? 'active' : ''; ?>">
                                    全部订单 <span class="badge bg-primary"><?php echo $total_orders; ?></span>
                                </a>
                                <a href="orders.php?status=0" class="btn btn-outline-warning <?php echo $status_filter === '0' ? 'active' : ''; ?>">
                                    待支付 <span class="badge bg-warning"><?php echo $pending_orders; ?></span>
                                </a>
                                <a href="orders.php?status=1" class="btn btn-outline-success <?php echo $status_filter === '1' ? 'active' : ''; ?>">
                                    已支付 <span class="badge bg-success"><?php echo $paid_orders; ?></span>
                                </a>
                                <a href="orders.php?status=2" class="btn btn-outline-info <?php echo $status_filter === '2' ? 'active' : ''; ?>">
                                    已完成 <span class="badge bg-info"><?php echo $completed_orders; ?></span>
                                </a>
                                <a href="orders.php?status=3" class="btn btn-outline-danger <?php echo $status_filter === '3' ? 'active' : ''; ?>">
                                    已取消 <span class="badge bg-danger"><?php echo $cancelled_orders; ?></span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 订单列表 -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                订单列表
                                <?php if (!empty($search_keyword) || !empty($category_id) || $status_filter !== ''): ?>
                                <small class="text-muted fs-6">
                                    - 搜索到 <?php echo $total_orders; ?> 条记录
                                </small>
                                <?php endif; ?>
                            </h5>
                            <div class="text-muted">
                                <small>最后更新: <?php echo date('Y-m-d H:i:s'); ?></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($search_keyword) || !empty($category_id) || $status_filter !== ''): ?>
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
                                
                                <?php if ($status_filter !== '' && (!empty($search_keyword) || !empty($category_id))): ?> | <?php endif; ?>
                                
                                <?php if ($status_filter !== ''): ?>
                                <strong>状态</strong>: 
                                <?php 
                                switch($status_filter) {
                                    case '0': echo '待支付'; break;
                                    case '1': echo '已支付'; break;
                                    case '2': echo '已完成'; break;
                                    case '3': echo '已取消'; break;
                                }
                                ?>
                                <?php endif; ?>
                                
                                - 共找到 <strong><?php echo $total_orders; ?></strong> 条记录
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
                                                <button class="btn btn-sm btn-info" onclick="showOrderDetail('<?php echo $order['order_no']; ?>')">
                                                    <i class="fas fa-eye"></i> 详情
                                                </button>
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
                                <?php if (!empty($search_keyword) || !empty($category_id) || $status_filter !== ''): ?>
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
                                    
                                    <?php if ($status_filter !== '' && (!empty($search_keyword) || !empty($category_id))): ?> | <?php endif; ?>
                                    
                                    <?php if ($status_filter !== ''): ?>
                                    <strong>状态</strong>: 
                                    <?php 
                                    switch($status_filter) {
                                        case '0': echo '待支付'; break;
                                        case '1': echo '已支付'; break;
                                        case '2': echo '已完成'; break;
                                        case '3': echo '已取消'; break;
                                    }
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

    <!-- 订单详情模态框 -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">订单详情 - <span id="detailOrderNo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailContent"></div>
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

        // 显示订单详情
        function showOrderDetail(orderNo) {
            document.getElementById('detailOrderNo').textContent = orderNo;
            
            // 加载订单详情
            fetch('get_order_detail.php?order_no=' + orderNo)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
                })
                .catch(error => {
                    document.getElementById('orderDetailContent').innerHTML = '<div class="alert alert-danger">加载失败</div>';
                    new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
                });
        }
    </script>
</body>
</html>