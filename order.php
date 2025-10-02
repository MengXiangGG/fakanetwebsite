<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
    $contact = trim($_POST['contact']);
    $coupon_code = trim($_POST['coupon_code'] ?? '');
    $coupon_id = intval($_POST['coupon_id'] ?? 0);
    $final_amount = floatval($_POST['final_amount'] ?? 0);
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    
    // 检查商品是否存在且可购买
    if (!isProductAvailable($product_id)) {
        die('商品不存在或已售罄');
    }
    
    $product = getProductWithStock($product_id);
    if (!$product || $product['stock'] < $quantity) {
        die('商品库存不足，当前库存：' . $product['stock']);
    }
    
    // 验证优惠券（如果提供了优惠券代码）
    if (!empty($coupon_code)) {
        $original_amount = $product['price'] * $quantity;
        $coupon_result = validateCoupon($coupon_code, $original_amount, $product['category_id']);
        if (!$coupon_result['valid']) {
            die('优惠券验证失败: ' . $coupon_result['message']);
        }
        // 使用验证后的金额
        $final_amount = $coupon_result['final_amount'];
        $discount_amount = $coupon_result['discount_amount'];
        $coupon_id = $coupon_result['coupon']['id'];
    } else {
        // 如果没有优惠券，计算总金额
        $final_amount = $product['price'] * $quantity;
    }
    
    // 创建订单
    $order_no = generateOrderNo();
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_no, product_id, product_name, price, quantity, contact_info, coupon_id, coupon_code, discount_amount, final_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order_no, 
        $product_id, 
        $product['name'], 
        $product['price'], 
        $quantity, // 这里保存数量
        $contact,
        $coupon_id,
        $coupon_code,
        $discount_amount,
        $final_amount
    ]);
    $order_id = $pdo->lastInsertId();
    
    // 如果金额为0，直接发放卡密
    if ($final_amount == 0) {
        if (handleZeroAmountOrder($order_no, $quantity)) {
            // 记录优惠券使用
            if ($coupon_id) {
                useCoupon($coupon_id, $order_no, $contact, $discount_amount);
            }
            // 跳转到成功页面
            header("Location: pay_return.php?out_trade_no=" . $order_no);
            exit;
        } else {
            die('系统错误，请稍后重试');
        }
    }
    
    // 跳转到支付页面，传递订单号
    header("Location: pay.php?order_no=" . $order_no);
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>