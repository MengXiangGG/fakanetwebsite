<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

// 获取网站统计信息
$stats = get_site_stats();

// 获取热门商品（包含库存信息）
$hot_products = $pdo->query("
    SELECT p.*, c.name as category_name,
           (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND status = 0) as stock
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 1 
    ORDER BY p.id DESC 
    LIMIT 6
")->fetchAll();

// 获取最近订单
$recent_orders = $pdo->query("SELECT o.*, p.name as product_name 
                             FROM orders o 
                             LEFT JOIN products p ON o.product_id = p.id 
                             ORDER BY o.id DESC LIMIT 10")->fetchAll();

// 修复：重新计算总销售额，使用 final_amount
$revenue_stmt = $pdo->query("SELECT SUM(final_amount) as total_revenue FROM orders WHERE status = 1");
$revenue_result = $revenue_stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_revenue'] = $revenue_result['total_revenue'] ?: 0;

// 计算实际收入（扣除费率）
$fee_info = getFeeInfo();
$net_revenue = calculateNetAmount($stats['total_revenue']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仪表盘 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-card {
            border: none;
            border-radius: 10px;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .orders-card { background: linear-gradient(135deg, #3498db, #2980b9); }
        .products-card { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .revenue-card { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .users-card { background: linear-gradient(135deg, #f39c12, #d35400); }
        .net-revenue-card { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .fee-card { background: linear-gradient(135deg, #e67e22, #d35400); }
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
                    <!-- 页面标题 -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>仪表盘</h2>
                        <div class="text-muted">
                            <i class="fas fa-user me-1"></i>
                            欢迎，<?php echo $_SESSION['admin_username'] ?? '管理员'; ?>
                        </div>
                    </div>

                    <!-- 费率信息提示 -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                            <div>
                                <h6 class="mb-1">费率说明</h6>
                                <p class="mb-0">
                                    当前交易费率为 <strong><?php echo $fee_info['rate_percent']; ?>%</strong>，
                                    实际到账金额为订单金额的 <strong><?php echo $fee_info['net_percent']; ?>%</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 统计卡片 -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="dashboard-card orders-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                                        <div class="stat-label">总订单数</div>
                                    </div>
                                    <i class="fas fa-shopping-cart card-icon"></i>
                                </div>
                                <div class="mt-2">
                                    <small>今日订单: <?php echo $stats['today_orders']; ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="dashboard-card products-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                                        <div class="stat-label">商品数量</div>
                                    </div>
                                    <i class="fas fa-box card-icon"></i>
                                </div>
                                <div class="mt-2">
                                    <small>已上架商品</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="dashboard-card revenue-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number">¥<?php echo number_format($stats['total_revenue'], 2); ?></div>
                                        <div class="stat-label">总销售额</div>
                                    </div>
                                    <i class="fas fa-money-bill-wave card-icon"></i>
                                </div>
                                <div class="mt-2">
                                    <small>订单总金额</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="dashboard-card net-revenue-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number">¥<?php echo number_format($net_revenue['net_amount'], 2); ?></div>
                                        <div class="stat-label">实际收入</div>
                                    </div>
                                    <i class="fas fa-chart-line card-icon"></i>
                                </div>
                                <div class="mt-2">
                                    <small>扣除费率后</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 收入详情卡片 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="dashboard-card fee-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number">¥<?php echo number_format($net_revenue['fee_amount'], 2); ?></div>
                                        <div class="stat-label">总手续费</div>
                                    </div>
                                    <i class="fas fa-percentage card-icon"></i>
                                </div>
                                <div class="mt-2">
                                    <small>费率: <?php echo $fee_info['rate_percent']; ?>%</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">收入分析</h6>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="border-end">
                                                <h5 class="text-success mb-1">¥<?php echo number_format($stats['total_revenue'], 2); ?></h5>
                                                <small class="text-muted">总销售额</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div>
                                                <h5 class="text-danger mb-1">-¥<?php echo number_format($net_revenue['fee_amount'], 2); ?></h5>
                                                <small class="text-muted">手续费</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <h4 class="text-primary">¥<?php echo number_format($net_revenue['net_amount'], 2); ?></h4>
                                        <small class="text-muted">实际收入 (<?php echo $fee_info['net_percent']; ?>%)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 最近订单 -->
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">最近订单</h5>
                                    <a href="orders.php" class="btn btn-sm btn-outline-primary">查看全部</a>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($recent_orders)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>订单号</th>
                                                    <th>商品</th>
                                                    <th>数量</th>
                                                    <th>金额</th>
                                                    <th>状态</th>
                                                    <th>时间</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <small><?php echo $order['order_no']; ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $order['quantity']; ?> 件</span>
                                                    </td>
                                                    <td>
                                                        <strong class="text-success">
                                                            ¥<?php echo number_format($order['final_amount'], 2); ?>
                                                        </strong>
                                                        <?php if ($order['quantity'] > 1): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            (<?php echo $order['quantity']; ?>×¥<?php echo number_format($order['price'], 2); ?>)
                                                        </small>
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
                                                    <td>
                                                        <small><?php echo date('m-d H:i', strtotime($order['created_at'])); ?></small>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                        <p>暂无订单</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 快捷操作 -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">快捷操作</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="categories.php" class="btn btn-outline-primary text-start">
                                            <i class="fas fa-plus me-2"></i>添加商品分类
                                        </a>
                                        <a href="products.php" class="btn btn-outline-success text-start">
                                            <i class="fas fa-box me-2"></i>添加新商品
                                        </a>
                                        <a href="cards.php" class="btn btn-outline-info text-start">
                                            <i class="fas fa-credit-card me-2"></i>管理卡密
                                        </a>
                                        <a href="orders.php" class="btn btn-outline-warning text-start">
                                            <i class="fas fa-list me-2"></i>查看所有订单
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 系统信息 -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">费率信息</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>交易费率</span>
                                            <span class="text-danger fw-bold"><?php echo $fee_info['rate_percent']; ?>%</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>实际到账比例</span>
                                            <span class="text-success fw-bold"><?php echo $fee_info['net_percent']; ?>%</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>总手续费</span>
                                            <span class="text-danger">¥<?php echo number_format($net_revenue['fee_amount'], 2); ?></span>
                                        </li>
                                        <li class="list-group-item">
                                            <small class="text-muted">注：所有收入自动扣除 <?php echo $fee_info['rate_percent']; ?>% 交易费率</small>
                                        </li>
                                    </ul>
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