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
        }
        .btn-purchase:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shopping-cart me-2"></i><?php echo SITE_NAME; ?>
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">返回首页</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                                <li class="breadcrumb-item"><a href="category.php?id=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                            </ol>
                        </nav>
                        
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
                        <!-- 在购买表单部分添加优惠券输入 -->
                        <?php if ($is_available): ?>
                        <form action="order.php" method="POST" id="purchaseForm">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            
                            <!-- 购买数量选择 - 不限制数量 -->
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
                            
                            <button type="submit" class="btn btn-purchase text-white w-100" id="submitBtn">
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

        // 表单提交前的验证
        document.getElementById('purchaseForm').addEventListener('submit', function(e) {
            const quantity = parseInt(document.getElementById('quantity').value);
            const contact = document.getElementById('contact').value.trim();
            
            if (quantity < 1) {
                e.preventDefault();
                alert('购买数量必须大于0');
                return;
            }
            
            if (!contact) {
                e.preventDefault();
                alert('请输入联系方式');
                return;
            }
            
            // 检查库存
            const stock = <?php echo $stock; ?>;
            if (quantity > stock) {
                e.preventDefault();
                alert('库存不足，当前库存：' + stock);
                return;
            }
        });
    </script>
</body>
</html>