<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

// 搜索参数
$search_contact = isset($_GET['search_contact']) ? trim($_GET['search_contact']) : '';

// 构建查询条件
$where_condition = '';
$params = [];

if (!empty($search_contact)) {
    $where_condition = "WHERE o.contact_info LIKE ?";
    $params[] = "%{$search_contact}%";
}

// 获取联系方式统计
$sql = "
    SELECT 
        o.contact_info,
        COUNT(o.id) as order_count,
        SUM(o.final_amount) as total_amount,
        MAX(o.created_at) as last_order_time,
        MIN(o.created_at) as first_order_time
    FROM orders o
    {$where_condition}
    GROUP BY o.contact_info
    ORDER BY total_amount DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contact_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 统计总数
$total_customers = count($contact_stats);
$total_revenue = array_sum(array_column($contact_stats, 'total_amount'));
$total_orders = array_sum(array_column($contact_stats, 'order_count'));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>客户统计 - <?php echo SITE_NAME; ?>后台</title>
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
                    <h2>客户购买统计</h2>

                    <!-- 统计卡片 -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo $total_customers; ?></h4>
                                            <small>总客户数</small>
                                        </div>
                                        <i class="fas fa-users fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
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
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>¥<?php echo number_format($total_revenue, 2); ?></h4>
                                            <small>总销售额</small>
                                        </div>
                                        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>¥<?php echo number_format($total_customers > 0 ? $total_revenue / $total_customers : 0, 2); ?></h4>
                                            <small>客单价</small>
                                        </div>
                                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 搜索表单 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">客户搜索</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <input type="text" name="search_contact" class="form-control" placeholder="输入联系方式（邮箱或QQ）进行模糊搜索..." value="<?php echo htmlspecialchars($search_contact); ?>">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">搜索</button>
                                    <?php if (!empty($search_contact)): ?>
                                    <a href="contact_stats.php" class="btn btn-outline-secondary w-100 mt-2">清除搜索</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 客户统计表格 -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">客户购买统计</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($contact_stats)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>联系方式</th>
                                            <th>订单数量</th>
                                            <th>总金额</th>
                                            <th>平均订单金额</th>
                                            <th>首次购买</th>
                                            <th>最后购买</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contact_stats as $index => $stat): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($stat['contact_info']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $stat['order_count']; ?> 单</span>
                                            </td>
                                            <td class="fw-bold text-success">
                                                ¥<?php echo number_format($stat['total_amount'], 2); ?>
                                            </td>
                                            <td class="text-info">
                                                ¥<?php echo number_format($stat['total_amount'] / $stat['order_count'], 2); ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('Y-m-d H:i', strtotime($stat['first_order_time'])); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo date('Y-m-d H:i', strtotime($stat['last_order_time'])); ?></small>
                                            </td>
                                            <td>
                                                <a href="orders.php?search_type=contact&search_keyword=<?php echo urlencode($stat['contact_info']); ?>" class="btn btn-sm btn-outline-primary">
                                                    查看订单
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>暂无客户数据</p>
                                <?php if (!empty($search_contact)): ?>
                                <p>未找到匹配的客户</p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>