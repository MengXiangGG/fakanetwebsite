<?php
require_once 'config.php';

/**
 * 创建易支付订单 - 使用API接口获取支付二维码
 */
function createEpayOrder($order_no, $amount, $type = 'alipay') {
    // 基础参数
    $params = [
        'pid' => EPAY_PID,
        'type' => $type,
        'out_trade_no' => $order_no,
        'notify_url' => SITE_URL . 'pay_notify.php',
        'return_url' => SITE_URL . 'pay_return.php',
        'name' => '商品购买-' . $order_no,
        'money' => number_format($amount, 2, '.', ''),
        'clientip' => get_client_ip(),
        'device' => 'pc',
        'sign_type' => 'MD5'
    ];
    
    // 生成签名
    $sign = generateEpaySign($params);
    $params['sign'] = $sign;
    
    // 调试信息
    error_log("=== 易支付API请求参数 ===");
    error_log("商户ID: " . EPAY_PID);
    error_log("密钥: " . EPAY_KEY);
    error_log("请求参数: " . print_r($params, true));
    error_log("签名字符串: " . $sign);
    
    // 使用API接口获取支付二维码
    $api_url = EPAY_URL . 'mapi.php';
    error_log("API地址: " . $api_url);
    
    // 发送POST请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("HTTP状态码: " . $http_code);
    error_log("CURL错误: " . $curl_error);
    error_log("API原始响应: " . $response);
    
    if ($response === false) {
        error_log("易支付API请求失败: " . $curl_error);
        return [
            'error' => true,
            'message' => 'API请求失败: ' . $curl_error
        ];
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        error_log("API响应JSON解析失败");
        return [
            'error' => true,
            'message' => 'API响应格式错误'
        ];
    }
    
    error_log("API解析结果: " . print_r($result, true));
    
    if ($result['code'] != 1) {
        error_log("易支付API返回错误: " . ($result['msg'] ?? '未知错误'));
        return [
            'error' => true,
            'message' => '支付接口错误: ' . ($result['msg'] ?? '未知错误')
        ];
    }
    
    // 返回支付信息
    return [
        'error' => false,
        'code' => $result['code'],
        'msg' => $result['msg'],
        'trade_no' => $result['trade_no'] ?? '',
        'payurl' => $result['payurl'] ?? '',
        'qrcode' => $result['qrcode'] ?? '',
        'urlscheme' => $result['urlscheme'] ?? ''
    ];
}

/**
 * 生成易支付签名
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
    
    error_log("签名字符串: " . $signStr);
    
    $sign = md5($signStr);
    
    error_log("生成的签名: " . $sign);
    
    return $sign;
}

/**
 * 验证支付回调签名
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
    
    error_log("接收到的签名: " . $received_sign);
    error_log("计算出的签名: " . $calculated_sign);
    
    return $calculated_sign === $received_sign;
}

/**
 * 查询订单状态
 */
function queryEpayOrder($order_no) {
    $api_url = EPAY_URL . 'api.php';
    
    $params = [
        'act' => 'order',
        'pid' => EPAY_PID,
        'key' => EPAY_KEY,
        'out_trade_no' => $order_no
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    
    if ($result && $result['code'] == 1) {
        return $result;
    }
    
    return false;
}

/**
 * 获取客户端IP
 */
function get_client_ip() {
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_X_FORWARDED')) {
        $ip = getenv('HTTP_X_FORWARDED');
    } elseif (getenv('HTTP_FORWARDED_FOR')) {
        $ip = getenv('HTTP_FORWARDED_FOR');
    } elseif (getenv('HTTP_FORWARDED')) {
        $ip = getenv('HTTP_FORWARDED');
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
?>