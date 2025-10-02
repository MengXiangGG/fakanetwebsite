<?php
require_once 'config.php';

/**
 * 创建易支付订单 - 根据官方文档修复
 */
function createEpayOrder($order_no, $amount, $type = 'alipay') {
    // 基础参数
    $params = [
        'pid' => EPAY_PID,
        'type' => $type,
        'out_trade_no' => $order_no,
        'notify_url' => SITE_URL . 'pay_notify.php',
        'return_url' => SITE_URL . 'pay_return.php',
        'name' => '商品购买',
        'money' => number_format($amount, 2, '.', ''),
        // sign 和 sign_type 不参与签名！
    ];
    
    // 生成签名（根据官方文档）
    $sign = generateEpaySign($params);
    
    // 添加签名参数
    $params['sign'] = $sign;
    $params['sign_type'] = 'MD5';
    
    // 调试信息
    error_log("=== 易支付请求参数 ===");
    error_log("参数: " . print_r($params, true));
    
    return EPAY_URL . 'submit.php?' . http_build_query($params);
}

/**
 * 生成易支付签名 - 根据官方文档
 */
function generateEpaySign($params) {
    // 1. 移除不参与签名的参数
    $sign_params = $params;
    unset($sign_params['sign']);
    unset($sign_params['sign_type']);
    
    // 2. 移除空值参数
    $sign_params = array_filter($sign_params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    // 3. 按照参数名ASCII码从小到大排序
    ksort($sign_params);
    
    // 4. 拼接成URL键值对格式，参数值不要进行url编码
    $signStr = '';
    foreach ($sign_params as $k => $v) {
        $signStr .= $k . '=' . $v . '&';
    }
    $signStr = rtrim($signStr, '&');
    
    // 5. 拼接商户密钥KEY进行MD5加密
    $signStr .= EPAY_KEY;
    
    $sign = md5($signStr);
    
    // 调试信息
    error_log("签名字符串: " . $signStr);
    error_log("生成签名: " . $sign);
    
    return $sign;
}

/**
 * 验证支付回调签名 - 根据官方文档修复
 */
function verifyEpayNotify() {
    $params = $_GET;
    
    if (!isset($params['sign']) || !isset($params['sign_type'])) {
        error_log("易支付回调: 缺少签名参数");
        return false;
    }
    
    $received_sign = $params['sign'];
    
    // 生成验证签名
    $calculated_sign = generateEpaySign($params);
    
    // 调试信息
    error_log("=== 易支付回调验证 ===");
    error_log("回调参数: " . print_r($params, true));
    error_log("收到签名: " . $received_sign);
    error_log("计算签名: " . $calculated_sign);
    error_log("验证结果: " . ($calculated_sign === $received_sign ? '成功' : '失败'));
    
    return $calculated_sign === $received_sign;
}

?>