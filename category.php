<?php
// category.php - 合并后的分类和商品详情页面
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 获取分类slug和product_id
$category_slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// 通过slug获取分类信息
$category = null;
$category_id = 0;
if (!empty($category_slug)) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE random_slug = ? AND status = 1");
    $stmt->execute([$category_slug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($category) {
        $category_id = $category['id'];
    }
}

// 获取所有可用分类
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);

// 如果没有指定分类或分类不存在，使用第一个分类
if (!$category && count($categories) > 0) {
    $category = $categories[0];
    $category_id = $category['id'];
    $category_slug = $category['random_slug'];
}

// 获取当前分类的商品
$products = [];
if ($category_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status = 1 ORDER BY id DESC");
    $stmt->execute([$category_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 获取当前选中的商品信息
$current_product = null;
if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $stmt->execute([$product_id]);
    $current_product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 如果product_id不存在，重定向到不带product_id的链接
    if (!$current_product) {
        header('Location: category.php?slug=' . $category_slug);
        exit;
    }
}

// 如果没有选中商品但有商品列表，默认选中第一个商品
if (!$current_product && count($products) > 0) {
    $current_product = $products[0];
    $product_id = $current_product['id'];
}

// 获取商品库存
if ($current_product) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as stock FROM cards WHERE product_id = ? AND status = 0");
    $stmt->execute([$current_product['id']]);
    $stock_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_product['stock'] = $stock_result['stock'] ?? 0;
}

// 为所有商品添加库存信息
foreach ($products as &$product) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as stock FROM cards WHERE product_id = ? AND status = 0");
    $stmt->execute([$product['id']]);
    $stock_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $product['stock'] = $stock_result['stock'] ?? 0;
}
unset($product);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_product ? htmlspecialchars($current_product['name']) : '商品列表'; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #1890ff;
            --primary-dark: #096dd9;
            --success: #52c41a;
            --warning: #faad14;
            --error: #ff4d4f;
            --text: #333;
            --text-secondary: #666;
            --text-light: #999;
            --border: #e8e8e8;
            --background: #f5f5f5;
            --card-bg: #fff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--background);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            min-height: auto;
            padding-top: 20px;
        }

        .header {
            background: var(--card-bg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            padding: 0;
            margin-bottom: 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 0;
            position: relative;
            min-height: auto;
            text-align: center;
            width: 100%;
        }

        .logo {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 30px;
            align-items: start;
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
            margin-top: 0;
        }

        /* 商品列表区域 */
        .products-section {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            max-height: 70vh;
        }

        .section-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            background: #fafafa;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: var(--text);
            text-align: center;
        }

        .products-grid {
            padding: 0;
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow-y: auto;
            max-height: 100%;
        }

        .products-grid::-webkit-scrollbar {
            width: 6px;
        }

        .products-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .products-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .products-grid::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .product-row {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s;
            background: var(--card-bg);
            min-height: 60px;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-row:hover {
            background: #fafafa;
        }

        .product-row.active {
            background: #f0f7ff;
            border-left: 3px solid var(--primary);
        }

        .product-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .product-info {
            flex: 1;
            min-width: 0;
            margin-right: 12px;
        }

        .product-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            flex-shrink: 0;
            min-width: 80px;
        }

        .product-price {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            white-space: nowrap;
        }

        .stock-badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
            white-space: nowrap;
        }

        .stock-very-high {
            background: #f6ffed;
            border: 1px solid #b7eb8f;
            color: var(--success);
        }

        .stock-high {
            background: #e6f7ff;
            border: 1px solid #91d5ff;
            color: var(--primary);
        }

        .stock-normal {
            background: #fff7e6;
            border: 1px solid #ffd591;
            color: var(--warning);
        }

        .stock-low {
            background: #fff2f0;
            border: 1px solid #ffccc7;
            color: var(--error);
        }

        .stock-out {
            background: #f5f5f5;
            border: 1px solid #d9d9d9;
            color: var(--text-light);
        }

        /* 购买面板 */
        .purchase-panel {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 20px;
            min-height: 200px;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            background: #fafafa;
        }

        .panel-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .product-description-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid var(--border);
            margin-bottom: 10px;
        }

        .product-description-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .product-full-description {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
            word-break: break-word;
            max-height: 120px;
            overflow-y: auto;
        }

        .product-full-description::-webkit-scrollbar {
            width: 4px;
        }

        .product-full-description::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .product-full-description::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
        }

        .price-card {
            background: #fafafa;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .price-row:last-child {
            margin-bottom: 0;
            padding-top: 8px;
            border-top: 1px solid var(--border);
            font-weight: 600;
        }

        .final-price {
            font-size: 16px;
            color: var(--primary);
            font-weight: 600;
        }

        .buy-btn {
            width: 100%;
            background: var(--primary);
            border: none;
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
        }

        .buy-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .buy-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .empty-state i {
            font-size: 32px;
            color: var(--border);
            margin-bottom: 12px;
        }

        .empty-state h4 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text);
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .purchase-panel {
                position: static;
            }
            
            .product-row {
                padding: 10px 12px;
                min-height: 55px;
            }
            
            .panel-body {
                padding: 16px;
            }
        }

        .payment-modal .modal-dialog {
            max-width: 500px;
            display: flex;
            align-items: center;
            min-height: calc(100vh - 1rem);
            margin: 0.5rem auto;
        }

        .payment-modal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #fafafa 0%, #f0f0f0 100%);
            border-bottom: 1px solid var(--border);
            padding: 20px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .payment-option {
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            margin: 12px 0;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--card-bg);
        }

        .payment-option:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .payment-option.selected {
            border-color: var(--primary);
            background: #f0f7ff;
            box-shadow: 0 4px 15px rgba(24, 144, 255, 0.15);
        }

        .payment-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .qr-modal .modal-dialog {
            max-width: 400px;
            display: flex;
            align-items: center;
            min-height: calc(100vh - 1rem);
            margin: 0.5rem auto;
        }

        .qr-modal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .qr-code img {
            max-width: 280px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            background: white;
        }

        .countdown-timer {
            font-size: 16px;
            font-weight: 600;
            color: var(--warning);
            margin: 16px 0;
            padding: 12px;
            background: #fff7e6;
            border-radius: 8px;
            border: 1px solid #ffd591;
        }

        .payment-success {
            padding: 30px 20px;
            text-align: center;
        }

        .payment-success i {
            font-size: 48px;
            color: var(--success);
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <!-- 头部 -->
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-shopping-cart"></i>
                    <?php echo $category ? htmlspecialchars($category['name']) : '商品分类'; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <!-- 商品列表 -->
            <div class="products-section">
                <div class="section-header">
                    <h2 class="section-title">商品列表</h2>
                </div>
                <div class="products-grid">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-row <?php echo $product['id'] == ($current_product['id'] ?? 0) ? 'active' : ''; ?>" 
                                 onclick="selectProduct(<?php echo $product['id']; ?>)">
                                <div class="product-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                </div>
                                <div class="product-meta">
                                    <div class="product-price">¥<?php echo $product['price']; ?></div>
                                    <?php 
                                    $stock_class = 'stock-out';
                                    $stock_text = '无库存';
                                    if ($product['stock'] > 100) {
                                        $stock_class = 'stock-very-high';
                                        $stock_text = '库存非常多';
                                    } elseif ($product['stock'] > 30) {
                                        $stock_class = 'stock-high';
                                        $stock_text = '库存很多';
                                    } elseif ($product['stock'] > 10) {
                                        $stock_class = 'stock-normal';
                                        $stock_text = '库存一般';
                                    } elseif ($product['stock'] > 0) {
                                        $stock_class = 'stock-low';
                                        $stock_text = '库存少量';
                                    }
                                    ?>
                                    <div class="stock-badge <?php echo $stock_class; ?>">
                                        <?php echo $stock_text; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h4>暂无商品</h4>
                            <p>该分类下暂时没有商品</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 购买面板 -->
            <?php if ($current_product): ?>
            <div class="purchase-panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class="fas fa-shopping-cart"></i>
                        立即购买
                    </h3>
                </div>
                <div class="panel-body">
                    <!-- 商品描述区域 -->
                    <div class="product-description-section">
                        <div class="product-description-title">
                            <i class="fas fa-info-circle"></i>
                            商品描述
                        </div>
                        <div class="product-full-description">
                            <?php 
                            $description = $current_product['description'] ?? '暂无描述';
                            echo nl2br(htmlspecialchars($description)); 
                            ?>
                        </div>
                    </div>
                    
                    <form id="purchaseForm">
                        <input type="hidden" name="product_id" value="<?php echo $current_product['id']; ?>">
                        
                        <div class="form-group">
                            <label for="quantity" class="form-label">购买数量</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="1" min="1" max="<?php echo $current_product['stock']; ?>" 
                                   <?php echo $current_product['stock'] == 0 ? 'disabled' : ''; ?> required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact" class="form-label">联系方式</label>
                            <input type="text" class="form-control" id="contact" name="contact" 
                                   placeholder="请输入邮箱或QQ号" required>
                        </div>
                        
                        <div class="price-card">
                            <div class="price-row">
                                <span>商品价格:</span>
                                <span id="originalPrice">¥<?php echo $current_product['price']; ?></span>
                            </div>
                            <div class="price-row">
                                <span>购买数量:</span>
                                <span id="quantityDisplay">1</span>
                            </div>
                            <div class="price-row">
                                <span>应付金额:</span>
                                <span class="final-price" id="finalAmount">¥<?php echo $current_product['price']; ?></span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="final_amount" id="finalAmountInput" value="<?php echo $current_product['price']; ?>">
                        
                        <button type="button" class="buy-btn" id="submitBtn" onclick="showPaymentModal()" 
                                <?php echo $current_product['stock'] == 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-credit-card"></i>
                            <?php if ($current_product['stock'] == 0): ?>
                                已售罄
                            <?php else: ?>
                                立即购买
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 支付方式选择模态框 -->
    <div class="modal fade payment-modal" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">选择支付方式</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- 订单信息 -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="mb-3">订单信息</h6>
                            <div class="row">
                                <div class="col-6"><small>商品名称:</small></div>
                                <div class="col-6 text-end"><small><?php echo htmlspecialchars($current_product['name'] ?? ''); ?></small></div>
                                <div class="col-6"><small>购买数量:</small></div>
                                <div class="col-6 text-end"><small><span id="modalQuantity">1</span> 件</small></div>
                                <div class="col-6"><small>应付金额:</small></div>
                                <div class="col-6 text-end"><small class="fw-bold text-danger" id="modalAmount">¥<?php echo $current_product['price'] ?? '0.00'; ?></small></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 支付方式选择 -->
                    <div class="mb-3">
                        <h6 class="mb-3">选择支付方式</h6>
                        <div class="payment-option" onclick="selectPayment('alipay')" id="alipayOption">
                            <div class="text-center">
                                <i class="fab fa-alipay payment-icon text-primary"></i>
                                <h6 class="mb-2">支付宝支付</h6>
                                <small class="text-muted">推荐使用支付宝扫码支付</small>
                            </div>
                        </div>
                        <div class="payment-option" onclick="selectPayment('wxpay')" id="wxpayOption">
                            <div class="text-center">
                                <i class="fab fa-weixin payment-icon text-success"></i>
                                <h6 class="mb-2">微信支付</h6>
                                <small class="text-muted">推荐使用微信扫码支付</small>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="selectedPayment" name="payment_method" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" id="confirmPayment" onclick="createOrder()" disabled>
                        <i class="fas fa-check me-2"></i>确认支付
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 支付二维码模态框 -->
    <div class="modal fade qr-modal" id="qrModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalTitle">扫码支付</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="cancelOrder()"></button>
                </div>
                <div class="modal-body text-center">
                    <!-- 加载状态 -->
                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">加载中...</span>
                        </div>
                        <p class="mt-3 fs-5">正在创建订单，请稍候...</p>
                    </div>
                    
                    <!-- 二维码显示 -->
                    <div class="qr-code" id="qrCode" style="display: none;">
                        <div id="qrCodeImage" class="mb-4"></div>
                        <p class="fs-5 mb-3">请使用<span id="paymentAppName" class="fw-bold">支付宝</span>扫描二维码完成支付</p>
                        <div class="countdown-timer" id="countdownTimer">60秒后自动取消</div>
                        <div class="alert alert-info mt-3">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                支付成功后会自动跳转到结果页面
                            </small>
                        </div>
                    </div>
                    
                    <!-- 支付成功 -->
                    <div class="payment-success" id="paymentSuccess" style="display: none;">
                        <i class="fas fa-check-circle text-success mb-3"></i>
                        <h5 class="mb-2">支付成功！</h5>
                        <p class="text-muted">正在跳转到订单页面...</p>
                    </div>
                </div>
                <div class="modal-footer justify-content-center" id="qrModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelBtn" onclick="cancelOrder()">
                        <i class="fas fa-times me-2"></i>取消支付
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let originalPrice = <?php echo $current_product ? $current_product['price'] : 0; ?>;
        let currentQuantity = 1;
        let selectedPaymentMethod = '';
        let currentOrderNo = '';
        let countdownTimer = null;
        let countdownSeconds = 60;
        let paymentCheckInterval = null;

        function changeCategory(slug) {
            window.location.href = 'category.php?slug=' + slug;
        }
        
        function selectProduct(productId) {
            const url = new URL(window.location.href);
            url.searchParams.set('product_id', productId);
            window.location.href = url.toString();
        }

        document.getElementById('quantity')?.addEventListener('change', function() {
            currentQuantity = parseInt(this.value) || 1;
            if (currentQuantity < 1) {
                currentQuantity = 1;
                this.value = 1;
            }
            document.getElementById('quantityDisplay').textContent = currentQuantity;
            updatePrice();
        });

        document.getElementById('quantity')?.addEventListener('input', function() {
            currentQuantity = parseInt(this.value) || 1;
            if (currentQuantity < 1) {
                currentQuantity = 1;
            }
            document.getElementById('quantityDisplay').textContent = currentQuantity;
            updatePrice();
        });

        function updatePrice() {
            const totalOriginalPrice = originalPrice * currentQuantity;
            document.getElementById('originalPrice').textContent = '¥' + originalPrice.toFixed(2);
            document.getElementById('finalAmount').textContent = '¥' + totalOriginalPrice.toFixed(2);
            document.getElementById('finalAmountInput').value = totalOriginalPrice;
        }

        function showPaymentModal() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const contact = document.getElementById('contact').value.trim();
            const stock = <?php echo $current_product ? $current_product['stock'] : 0; ?>;
            
            if (quantity < 1) {
                alert('购买数量必须大于0');
                return;
            }
            
            if (!contact) {
                alert('请输入联系方式');
                return;
            }
            
            if (quantity > stock) {
                alert('库存不足，当前库存：' + stock);
                return;
            }
            
            document.getElementById('modalQuantity').textContent = quantity;
            document.getElementById('modalAmount').textContent = document.getElementById('finalAmount').textContent;
            
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        }

        function selectPayment(method) {
            selectedPaymentMethod = method;
            
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            document.getElementById(method + 'Option').classList.add('selected');
            
            document.getElementById('confirmPayment').disabled = false;
        }

        function createOrder() {
            if (!selectedPaymentMethod) {
                alert('请选择支付方式');
                return;
            }
            
            const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            paymentModal.hide();
            
            const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('qrCode').style.display = 'none';
            document.getElementById('paymentSuccess').style.display = 'none';
            qrModal.show();
            
            const paymentAppName = selectedPaymentMethod === 'alipay' ? '支付宝' : '微信';
            document.getElementById('paymentAppName').textContent = paymentAppName;
            document.getElementById('qrModalTitle').textContent = paymentAppName + '支付';
            
            const formData = new FormData(document.getElementById('purchaseForm'));
            formData.append('payment_method', selectedPaymentMethod);
            
            fetch('order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('订单创建响应:', data);
                document.getElementById('loadingSpinner').style.display = 'none';
                
                if (data.success) {
                    currentOrderNo = data.order_no;
                    
                    if (data.payment_result) {
                        const paymentResult = data.payment_result;
                        console.log('支付结果:', paymentResult);
                        
                        document.getElementById('qrCode').style.display = 'block';

                        if (paymentResult.qrcode) {
                            console.log('二维码URL:', paymentResult.qrcode);
                            const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=${encodeURIComponent(paymentResult.qrcode)}&format=png&margin=15`;
                            document.getElementById('qrCodeImage').innerHTML = 
                                `<img src="${qrCodeUrl}" alt="支付二维码" style="max-width: 280px; height: auto;">`;
                        } else {
                            document.getElementById('qrCodeImage').innerHTML = 
                                '<div style="color: red; padding: 20px; font-size: 16px;">没有获取到支付二维码</div>';
                        }
                        
                        startCountdown();
                        startPaymentCheck();
                        
                    } else {
                        alert('支付信息获取失败，请重试');
                        bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
                    }
                } else {
                    alert('订单创建失败：' + (data.message || '未知错误'));
                    bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
                }
            })
            .catch(error => {
                console.error('网络错误:', error);
                document.getElementById('loadingSpinner').style.display = 'none';
                alert('网络错误，请重试');
                bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
            });
        }

        function startCountdown() {
            countdownSeconds = 60;
            updateCountdownDisplay();
            
            countdownTimer = setInterval(function() {
                countdownSeconds--;
                updateCountdownDisplay();
                
                if (countdownSeconds <= 0) {
                    clearInterval(countdownTimer);
                    autoCancelOrder();
                }
            }, 1000);
        }

        function updateCountdownDisplay() {
            document.getElementById('countdownTimer').textContent = countdownSeconds + '秒后自动取消订单';
        }

        function autoCancelOrder() {
            if (!currentOrderNo) return;
            
            const formData = new FormData();
            formData.append('cancel_order', 'true');
            formData.append('order_no', currentOrderNo);
            
            fetch('cancel_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (paymentCheckInterval) {
                        clearInterval(paymentCheckInterval);
                    }
                    bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
                    alert('订单已自动取消（超时未支付）');
                    currentOrderNo = '';
                } else {
                    alert('自动取消订单失败: ' + data.message);
                    bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
            });
        }

        function startPaymentCheck() {
            if (!currentOrderNo) return;
            
            if (paymentCheckInterval) {
                clearInterval(paymentCheckInterval);
            }
            
            paymentCheckInterval = setInterval(function() {
                fetch('check_payment.php?order_no=' + currentOrderNo)
                    .then(response => response.json())
                    .then(data => {
                        if (data.paid) {
                            clearInterval(countdownTimer);
                            clearInterval(paymentCheckInterval);
                            document.getElementById('qrCode').style.display = 'none';
                            document.getElementById('paymentSuccess').style.display = 'block';
                            document.getElementById('cancelBtn').style.display = 'none';
                            
                            setTimeout(function() {
                                window.location.href = 'pay_return.php?out_trade_no=' + currentOrderNo;
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('支付状态检查失败:', error);
                    });
            }, 3000);
        }

        function cancelOrder() {
            if (!currentOrderNo) {
                bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
                return;
            }
            
            if (confirm('确定要取消这个订单吗？')) {
                const formData = new FormData();
                formData.append('cancel_order', 'true');
                formData.append('order_no', currentOrderNo);
                
                fetch('cancel_order.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (countdownTimer) {
                            clearInterval(countdownTimer);
                        }
                        if (paymentCheckInterval) {
                            clearInterval(paymentCheckInterval);
                        }
                        bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
                        alert('订单已取消');
                        currentOrderNo = '';
                    } else {
                        alert('取消订单失败: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('网络错误，请重试');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updatePrice();
            
            document.getElementById('qrModal').addEventListener('hidden.bs.modal', function() {
                if (countdownTimer) {
                    clearInterval(countdownTimer);
                }
                if (paymentCheckInterval) {
                    clearInterval(paymentCheckInterval);
                }
                currentOrderNo = '';
            });
        });
    </script>
</body>
</html>