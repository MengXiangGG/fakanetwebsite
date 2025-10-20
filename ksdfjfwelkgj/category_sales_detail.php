<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die('Access Denied');
}

require_once '../includes/functions.php';

$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$category_id) {
    die('参数错误');
}

// 获取分类信息
$category = getCategoryById($category_id);
if (!$category) {
    die('分类不存在');
}

// 获取销售统计
$sales_stats = getCategorySalesStats($category_id);
$sales_trend = getCategorySalesTrend($category_id, 7);

// 获取分类下的商品
$products = getProductsByCategory($category_id);
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">销售概览</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-end">
                            <h4 class="text-success">¥<?php echo number_format($sales_stats['today_sales'], 2); ?></h4>
                            <small class="text-muted">今日销售额</small>
                            <div class="mt-1">
                                <span class="badge bg-success"><?php echo $sales_stats['today_orders']; ?> 单</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <h4 class="text-info">¥<?php echo number_format($sales_stats['yesterday_sales'], 2); ?></h4>
                            <small class="text-muted">昨日销售额</small>
                            <div class="mt-1">
                                <span class="badge bg-info"><?php echo $sales_stats['yesterday_orders']; ?> 单</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div>
                            <h4 class="text-warning">¥<?php echo number_format($sales_stats['total_sales'], 2); ?></h4>
                            <small class="text-muted">总销售额</small>
                            <div class="mt-1">
                                <span class="badge bg-warning"><?php echo $sales_stats['total_orders']; ?> 单</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 增长趋势 -->
                <div class="mt-3 p-3 bg-light rounded">
                    <?php
                    $growth_rate = calculateSalesGrowth($sales_stats['today_sales'], $sales_stats['yesterday_sales']);
                    $trend = getSalesTrendIcon($sales_stats['today_sales'], $sales_stats['yesterday_sales']);
                    ?>
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="<?php echo $trend['icon']; ?> fa-2x text-<?php echo $trend['color']; ?> me-3"></i>
                        <div>
                            <h5 class="mb-1 text-<?php echo $trend['color']; ?>">较昨日<?php echo $trend['text']; ?> <?php echo $growth_rate; ?>%</h5>
                            <small class="text-muted">
                                差额: ¥<?php echo number_format($sales_stats['today_sales'] - $sales_stats['yesterday_sales'], 2); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">商品信息</h6>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <h4 class="text-primary"><?php echo count($products); ?></h4>
                    <small class="text-muted">商品数量</small>
                </div>
                <?php if (!empty($products)): ?>
                <div class="mt-3">
                    <small class="text-muted">商品列表：</small>
                    <div class="mt-1">
                        <?php foreach (array_slice($products, 0, 3) as $product): ?>
                        <span class="badge bg-light text-dark me-1 mb-1"><?php echo htmlspecialchars($product['name']); ?></span>
                        <?php endforeach; ?>
                        <?php if (count($products) > 3): ?>
                        <span class="badge bg-secondary">+<?php echo count($products) - 3; ?>更多</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 销售趋势 -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">最近7天销售趋势</h6>
    </div>
    <div class="card-body">
        <?php if (!empty($sales_trend)): ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>日期</th>
                        <th>销售额</th>
                        <th>订单数</th>
                        <th>趋势</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_trend as $day): ?>
                    <tr>
                        <td>
                            <?php 
                            $date = new DateTime($day['sale_date']);
                            $today = new DateTime();
                            $yesterday = new DateTime('yesterday');
                            
                            if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
                                echo '<strong>今日</strong>';
                            } elseif ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                                echo '<strong>昨日</strong>';
                            } else {
                                echo $day['sale_date'];
                            }
                            ?>
                        </td>
                        <td class="fw-bold <?php echo $date->format('Y-m-d') === $today->format('Y-m-d') ? 'text-success' : ($date->format('Y-m-d') === $yesterday->format('Y-m-d') ? 'text-info' : 'text-dark'); ?>">
                            ¥<?php echo number_format($day['daily_sales'], 2); ?>
                        </td>
                        <td><?php echo $day['daily_orders']; ?></td>
                        <td>
                            <?php if ($day['daily_sales'] > 0): ?>
                            <span class="badge bg-success">有销售</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">无销售</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-3">
            <i class="fas fa-chart-line fa-2x mb-2"></i>
            <p>暂无销售数据</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 快捷操作 -->
<div class="mt-3">
    <a href="products.php" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-box"></i> 管理商品
    </a>
    <a href="orders.php" class="btn btn-outline-success btn-sm">
        <i class="fas fa-shopping-cart"></i> 查看订单
    </a>
</div>