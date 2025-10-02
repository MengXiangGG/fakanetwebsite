<?php
require_once 'includes/functions.php';
require_once 'includes/epay.php';

$order_no = isset($_GET['order_no']) ? $_GET['order_no'] : '';

if (empty($order_no)) {
    header('Location: index.php');
    exit;
}

// 获取订单信息
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ?");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('订单不存在');
}

if ($order['status'] == 1) {
    die('订单已支付');
}

// 计算总金额（单价 × 数量）
$quantity = $order['quantity'] ?? 1;
$total_amount = $order['final_amount']; // 这里已经是计算好的总金额

// 生成支付链接
$alipay_url = createEpayOrder($order_no, $total_amount, 'alipay');
$wxpay_url = createEpayOrder($order_no, $total_amount, 'wxpay');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支付订单 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .payment-amount {
            font-size: 2rem;
            font-weight: bold;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>订单支付</h4>
                    </div>
                    <div class="card-body">
                        <!-- 订单信息 -->
                        <div class="order-summary">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <strong>订单号：</strong> <?php echo $order['order_no']; ?>
                                </div>
                                <div class="col-12 mb-3">
                                    <strong>商品名称：</strong> <?php echo htmlspecialchars($order['product_name']); ?>
                                </div>
                                <div class="col-12 mb-3">
                                    <strong>购买数量：</strong> 
                                    <span class="badge bg-info"><?php echo $quantity; ?> 件</span>
                                </div>
                                <div class="col-12 mb-3">
                                    <strong>商品单价：</strong> ¥<?php echo number_format($order['price'], 2); ?>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                <div class="col-12 mb-3">
                                    <strong>优惠金额：</strong> 
                                    <span class="text-success">-¥<?php echo number_format($order['discount_amount'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="col-12">
                                    <div class="text-center">
                                        <div class="payment-amount">¥<?php echo number_format($total_amount, 2); ?></div>
                                        <small class="text-muted">应付金额</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 支付方式 -->
                        <div class="mt-4">
                            <h5>选择支付方式</h5>
                            <div class="d-grid gap-2">
                                <a href="<?php echo $alipay_url; ?>" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-money-bill-wave me-2"></i> 支付宝支付
                                </a>
                                <a href="<?php echo $wxpay_url; ?>" class="btn btn-outline-success btn-lg">
                                    <i class="fas fa-comment-dollar me-2"></i> 微信支付
                                </a>
                            </div>
                        </div>
                        
                        <!-- 温馨提示 -->
                        <div class="alert alert-info mt-4">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                支付完成后，系统将自动发放卡密到您的邮箱。
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>