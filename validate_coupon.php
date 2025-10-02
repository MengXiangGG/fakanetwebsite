<?php
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coupon_code = safe_input($_POST['coupon_code']);
    $amount = floatval($_POST['amount']);
    $product_id = intval($_POST['product_id']);
    
    // 获取商品分类
    $product = getProductById($product_id);
    $category_id = $product ? $product['category_id'] : null;
    
    $result = validateCoupon($coupon_code, $amount, $category_id);
    
    echo json_encode($result);
} else {
    echo json_encode(['valid' => false, 'message' => '无效请求']);
}
?>