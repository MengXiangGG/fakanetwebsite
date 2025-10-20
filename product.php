<?php
require_once 'includes/functions.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = getProductById($product_id);

if (!$product) {
    header('Location: index.php');
    exit;
}

// 获取商品库存
$stock = getProductStock($product_id);
$is_available = ($stock > 0);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .product-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .price-tag {
            font-size: 2rem;
            font-weight: bold;
            color: #e74c3c;
        }
        .stock-badge {
            font-size: 1rem;
            padding: 8px 15px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: none;
        }
        .btn-purchase {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 30px;
            font-size: 1.2rem;
            border-radius: 10px;
            color: white;
        }
        .btn-purchase:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            color: white;
        }
        .payment-modal .modal-content {
            border-radius: 15px;
            border: none;
        }
        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-option:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .payment-option.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .qr-code {
            text-align: center;
            padding: 20px;
        }
        .qr-code img {
            max-width: 200px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .payment-success {
            text-align: center;
            padding: 20px;
            display: none;
        }
        .countdown-timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
            margin: 10px 0;
        }
        .btn-cancel {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        .btn-cancel:hover {
            background: #5a6268;
            border-color: #545b62;
            color: white;
        }
        /* 页面布局调整 */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            flex: 1;
        }
        footer {
            margin-top: auto;
        }
        /* 扫码支付模态框居中 */
        .qr-modal-center .modal-dialog {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }
        .qr-modal-center .modal-content {
            margin: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-shopping-cart me-2"></i><?php echo SITE_NAME; ?>
            </span>
            <a href="index.php" class="btn btn-outline-light btn-sm">返回首页</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        
                        <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <?php if ($product['description']): ?>
                        <div class="mb-4">
                            <p class="lead"><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex align-items-center mb-4">
                            <span class="price-tag me-3">¥<?php echo number_format($product['price'], 2); ?></span>
                            <span class="stock-badge badge bg-<?php echo $is_available ? 'success' : 'danger'; ?>">
                                <i class="fas fa-<?php echo $is_available ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo $is_available ? '有货' : '缺货'; ?>
                                (库存: <?php echo $stock; ?>)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>立即购买</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($is_available): ?>
                        <form id="purchaseForm">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            
                            <!-- 购买数量选择 -->
                            <div class="mb-3">
                                <label for="quantity" class="form-label">购买数量</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" required>
                                <div class="form-text">请输入需要购买的数量</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact" class="form-label">联系方式（邮箱或QQ）</label>
                                <input type="text" class="form-control" id="contact" name="contact" placeholder="请输入邮箱或QQ号" required>
                            </div>
                            
                            <!-- 优惠券输入 -->
                            <div class="mb-3">
                                <label for="coupon_code" class="form-label">优惠券代码（可选）</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="coupon_code" name="coupon_code" placeholder="输入优惠券代码">
                                    <button type="button" class="btn btn-outline-secondary" onclick="validateCoupon()">验证</button>
                                </div>
                                <div id="couponMessage" class="form-text"></div>
                            </div>
                            
                            <!-- 价格显示 -->
                            <div class="mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>商品价格:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span id="originalPrice">¥<?php echo $product['price']; ?></span> × <span id="quantityDisplay">1</span>
                                            </div>
                                        </div>
                                        <div class="row mt-2" id="discountRow" style="display: none;">
                                            <div class="col-6">
                                                <strong>优惠金额:</strong>
                                            </div>
                                            <div class="col-6 text-end text-success">
                                                -<span id="discountAmount">¥0.00</span>
                                            </div>
                                        </div>
                                        <div class="row mt-2 border-top pt-2">
                                            <div class="col-6">
                                                <strong>应付金额:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong class="text-danger" id="finalAmount">¥<?php echo $product['price']; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="final_amount" id="finalAmountInput" value="<?php echo $product['price']; ?>">
                            <input type="hidden" name="discount_amount" id="discountAmountInput" value="0">
                            <input type="hidden" name="coupon_id" id="couponIdInput" value="">
                            
                            <button type="button" class="btn btn-purchase w-100" id="submitBtn" onclick="showPaymentModal()">
                                <i class="fas fa-credit-card me-2"></i>立即购买
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h5>该商品暂时缺货</h5>
                            <p class="mb-0">请关注商品补货通知</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 商品信息卡片 -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>商品信息</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>商品分类</span>
                                <span class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>商品编号</span>
                                <span class="text-muted">#<?php echo $product['id']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>当前库存</span>
                                <span class="text-<?php echo $stock > 10 ? 'success' : ($stock > 0 ? 'warning' : 'danger'); ?>">
                                    <?php echo $stock; ?> 件
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>上架时间</span>
                                <span class="text-muted"><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- 支付方式选择模态框 -->
<div class="modal fade payment-modal qr-modal-center" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">选择支付方式</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- 订单信息 -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6>订单信息</h6>
                        <div class="row small">
                            <div class="col-6">商品名称:</div>
                            <div class="col-6"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="col-6">购买数量:</div>
                            <div class="col-6"><span id="modalQuantity">1</span> 件</div>
                            <div class="col-6">应付金额:</div>
                            <div class="col-6 fw-bold text-danger" id="modalAmount">¥<?php echo $product['price']; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- 支付方式选择 -->
                <div class="mb-3">
                    <h6>选择支付方式</h6>
                    <div class="payment-option" onclick="selectPayment('alipay')" id="alipayOption">
                        <div class="text-center">
                            <i class="fas fa-money-bill-wave payment-icon text-primary"></i>
                            <h6>支付宝支付</h6>
                            <small class="text-muted">推荐使用支付宝扫码支付</small>
                        </div>
                    </div>
                    <div class="payment-option" onclick="selectPayment('wxpay')" id="wxpayOption">
                        <div class="text-center">
                            <i class="fas fa-comment-dollar payment-icon text-success"></i>
                            <h6>微信支付</h6>
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
    <div class="modal fade qr-modal-center" id="qrModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalTitle">扫码支付</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="cancelOrder()"></button>
                </div>
                <div class="modal-body text-center">
                    <!-- 加载状态 -->
                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">加载中...</span>
                        </div>
                        <p class="mt-3">正在创建订单，请稍候...</p>
                    </div>
                    
                    <!-- 二维码显示 -->
                    <div class="qr-code" id="qrCode" style="display: none;">
                        <div id="qrCodeImage"></div>
                        <p class="mt-2">请使用<span id="paymentAppName">支付宝</span>扫描二维码完成支付</p>
                        <div class="countdown-timer" id="countdownTimer">60秒后自动跳转</div>
                        <div class="alert alert-info mt-2">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                支付成功后会自动跳转到结果页面
                            </small>
                        </div>
                    </div>
                    
                    <!-- 支付成功 -->
                    <div class="payment-success" id="paymentSuccess">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>支付成功！</h5>
                        <p>正在跳转到订单页面...</p>
                    </div>
                </div>
                <div class="modal-footer justify-content-center" id="qrModalFooter">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal" id="cancelBtn" onclick="cancelOrder()">
                        <i class="fas fa-times me-2"></i>取消支付
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 版权信息放到最下面 -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2023 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let originalPrice = <?php echo $product['price']; ?>;
        let currentCoupon = null;
        let currentQuantity = 1;
        let selectedPaymentMethod = '';
        let currentOrderNo = '';
        let countdownTimer = null;
        let countdownSeconds = 60;
        let paymentCheckInterval = null;

        // 数量变化事件
        document.getElementById('quantity').addEventListener('change', function() {
            currentQuantity = parseInt(this.value) || 1;
            if (currentQuantity < 1) {
                currentQuantity = 1;
                this.value = 1;
            }
            document.getElementById('quantityDisplay').textContent = currentQuantity;
            updatePrice();
        });

        // 输入事件，实时更新
        document.getElementById('quantity').addEventListener('input', function() {
            currentQuantity = parseInt(this.value) || 1;
            if (currentQuantity < 1) {
                currentQuantity = 1;
            }
            document.getElementById('quantityDisplay').textContent = currentQuantity;
            updatePrice();
        });

        function updatePrice() {
            const totalOriginalPrice = originalPrice * currentQuantity;
            
            if (currentCoupon) {
                // 重新验证优惠券
                validateCoupon();
            } else {
                document.getElementById('originalPrice').textContent = '¥' + originalPrice.toFixed(2);
                document.getElementById('finalAmount').textContent = '¥' + totalOriginalPrice.toFixed(2);
                document.getElementById('finalAmountInput').value = totalOriginalPrice;
            }
        }

        function validateCoupon() {
            const couponCode = document.getElementById('coupon_code').value;
            const productId = <?php echo $product['id']; ?>;
            const quantity = document.getElementById('quantity').value;
            const totalAmount = originalPrice * quantity;
            
            if (!couponCode) {
                showCouponMessage('请输入优惠券代码', 'warning');
                return;
            }
            
            // 发送AJAX请求验证优惠券
            fetch('validate_coupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `coupon_code=${couponCode}&amount=${totalAmount}&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    currentCoupon = data.coupon;
                    updatePriceDisplay(data.discount_amount, data.final_amount, totalAmount);
                    showCouponMessage(`优惠券验证成功！优惠 ${data.discount_amount}元`, 'success');
                    
                    // 更新隐藏字段
                    document.getElementById('finalAmountInput').value = data.final_amount;
                    document.getElementById('discountAmountInput').value = data.discount_amount;
                    document.getElementById('couponIdInput').value = data.coupon.id;
                    
                    // 如果金额为0，修改按钮文字
                    if (data.final_amount == 0) {
                        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-gift me-2"></i>免费领取';
                    } else {
                        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-credit-card me-2"></i>立即购买';
                    }
                } else {
                    currentCoupon = null;
                    resetPriceDisplay(totalAmount);
                    showCouponMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                showCouponMessage('验证失败，请重试', 'danger');
            });
        }

        function updatePriceDisplay(discount, final, totalAmount) {
            document.getElementById('discountRow').style.display = 'flex';
            document.getElementById('discountAmount').textContent = '¥' + discount.toFixed(2);
            document.getElementById('finalAmount').textContent = '¥' + final.toFixed(2);
            document.getElementById('originalPrice').textContent = '¥' + totalAmount.toFixed(2);
        }

        function resetPriceDisplay(totalAmount) {
            document.getElementById('discountRow').style.display = 'none';
            document.getElementById('finalAmount').textContent = '¥' + totalAmount.toFixed(2);
            document.getElementById('originalPrice').textContent = '¥' + totalAmount.toFixed(2);
            document.getElementById('finalAmountInput').value = totalAmount;
            document.getElementById('discountAmountInput').value = 0;
            document.getElementById('couponIdInput').value = '';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-credit-card me-2"></i>立即购买';
        }

        function showCouponMessage(message, type) {
            const messageEl = document.getElementById('couponMessage');
            messageEl.textContent = message;
            messageEl.className = 'form-text text-' + type;
        }

        // 显示支付模态框
        function showPaymentModal() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const contact = document.getElementById('contact').value.trim();
            const stock = <?php echo $stock; ?>;
            
            // 验证表单
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
            
            // 更新模态框中的信息
            document.getElementById('modalQuantity').textContent = quantity;
            document.getElementById('modalAmount').textContent = document.getElementById('finalAmount').textContent;
            
            // 显示支付模态框
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        }

        // 选择支付方式
        function selectPayment(method) {
            selectedPaymentMethod = method;
            
            // 移除所有选中状态
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // 添加选中状态
            document.getElementById(method + 'Option').classList.add('selected');
            
            // 启用确认按钮
            document.getElementById('confirmPayment').disabled = false;
        }

        // 创建订单
        function createOrder() {
            if (!selectedPaymentMethod) {
                alert('请选择支付方式');
                return;
            }
            
            // 关闭支付选择模态框
            const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            paymentModal.hide();
            
            // 显示二维码模态框
            const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('qrCode').style.display = 'none';
            document.getElementById('paymentSuccess').style.display = 'none';
            qrModal.show();
            
            // 设置支付方式名称
            const paymentAppName = selectedPaymentMethod === 'alipay' ? '支付宝' : '微信';
            document.getElementById('paymentAppName').textContent = paymentAppName;
            document.getElementById('qrModalTitle').textContent = paymentAppName + '支付';
            
            // 收集表单数据
            const formData = new FormData(document.getElementById('purchaseForm'));
            formData.append('payment_method', selectedPaymentMethod);
            
            fetch('order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('订单创建响应:', data);
                
                // 隐藏加载动画
                document.getElementById('loadingSpinner').style.display = 'none';
                
                if (data.success) {
                    currentOrderNo = data.order_no;
                    
                    if (data.payment_result) {
                        const paymentResult = data.payment_result;
                        console.log('支付结果:', paymentResult);
                        
                        // 显示二维码
                        document.getElementById('qrCode').style.display = 'block';
                        
                        if (paymentResult.qrcode) {
                            console.log('使用qrcode:', paymentResult.qrcode);
                            // 显示真正的支付二维码
                            document.getElementById('qrCodeImage').innerHTML = 
                                `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(paymentResult.qrcode)}" alt="支付二维码">`;
                            
                        } else if (paymentResult.payurl) {
                            console.log('使用payurl:', paymentResult.payurl);
                            // 如果有支付跳转URL，显示跳转二维码
                            document.getElementById('qrCodeImage').innerHTML = 
                                `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(paymentResult.payurl)}" alt="支付二维码">`;
                            
                        } else {
                            console.log('使用默认支付页面');
                            // 如果没有二维码，显示跳转到支付页面的二维码
                            const payPageUrl = window.location.origin + '/pay.php?order_no=' + currentOrderNo;
                            document.getElementById('qrCodeImage').innerHTML = 
                                `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(payPageUrl)}" alt="支付二维码">`;
                        }
                        
                        // 开始倒计时
                        startCountdown();
                        
                        // 开始轮询支付状态
                        startPaymentCheck();
                        
                    } else {
                        console.error('没有payment_result数据');
                        alert('支付信息获取失败，请重试');
                        bootstrap.Modal.getInstance(document.getElementById('qrModal')).hide();
                    }
                } else {
                    console.error('订单创建失败:', data.message);
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

// 开始倒计时
function startCountdown() {
    countdownSeconds = 60;
    updateCountdownDisplay();
    
    countdownTimer = setInterval(function() {
        countdownSeconds--;
        updateCountdownDisplay();
        
        if (countdownSeconds <= 0) {
            clearInterval(countdownTimer);
            // 倒计时结束，自动取消订单
            autoCancelOrder();
        }
    }, 1000);
}

// 更新倒计时显示
function updateCountdownDisplay() {
    document.getElementById('countdownTimer').textContent = countdownSeconds + '秒后自动取消订单';
}

// 自动取消订单
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
            // 清除支付状态检查
            if (paymentCheckInterval) {
                clearInterval(paymentCheckInterval);
            }
            
            // 关闭模态框
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

        // 开始支付状态检查
        function startPaymentCheck() {
            if (!currentOrderNo) return;
            
            // 清除之前的检查间隔
            if (paymentCheckInterval) {
                clearInterval(paymentCheckInterval);
            }
            
            // 每3秒检查一次支付状态
            paymentCheckInterval = setInterval(function() {
                fetch('check_payment.php?order_no=' + currentOrderNo)
                    .then(response => response.json())
                    .then(data => {
                        if (data.paid) {
                            // 支付成功
                            clearInterval(countdownTimer);
                            clearInterval(paymentCheckInterval);
                            document.getElementById('qrCode').style.display = 'none';
                            document.getElementById('paymentSuccess').style.display = 'block';
                            document.getElementById('cancelBtn').style.display = 'none';
                            
                            // 2秒后跳转到结果页面
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

        // 取消订单 - 修复网络错误
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
                        // 清除所有定时器
                        if (countdownTimer) {
                            clearInterval(countdownTimer);
                        }
                        if (paymentCheckInterval) {
                            clearInterval(paymentCheckInterval);
                        }
                        
                        // 关闭模态框
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

        // 页面加载后初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化价格显示
            updatePrice();
            
            // 监听模态框关闭事件
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