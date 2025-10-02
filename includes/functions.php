<?php
// 引入数据库配置
require_once 'config.php';


/**
 * 生成随机标识符
 */
function generateRandomSlug($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return 'cat' . $randomString;
}

function setSecurityHeaders() {
    // 防止点击劫持
    header('X-Frame-Options: DENY');
    // 防止MIME类型嗅探
    header('X-Content-Type-Options: nosniff');
    // XSS保护
    header('X-XSS-Protection: 1; mode=block');
    // 禁用缓存（对于敏感页面）
    if (basename($_SERVER['PHP_SELF']) === 'pay.php' || basename($_SERVER['PHP_SELF']) === 'order.php') {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

/**
 * 根据slug获取分类信息
 */
function getCategoryBySlug($slug) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE random_slug = ? AND status = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取分类失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 安全过滤输入
 */
function safe_input($data) {
    if (empty($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * 获取所有启用状态的分类
 */
function getCategories() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC, id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取分类失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 根据ID获取分类信息
 */
function getCategoryById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND status = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取分类失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 获取分类下的商品
 */
function getProductsByCategory($category_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status = 1 ORDER BY id DESC");
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取商品失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 根据ID获取商品信息
 */
function getProductById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              WHERE p.id = ? AND p.status = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取商品失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 生成订单号
 */
function generateOrderNo() {
    return date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 获取可用卡密
 */
function getAvailableCard($product_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE product_id = ? AND status = 0 LIMIT 1");
        $stmt->execute([$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取卡密失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 记录操作日志
 */
function log_action($action, $details = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (action, details, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("记录日志失败: " . $e->getMessage());
    }
}

// 自动创建必要的数据库表（如果不存在）
function create_tables_if_not_exist() {
    global $pdo;
    
    $tables = [
        "admin_users" => "CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "categories" => "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            status TINYINT DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "products" => "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock INT DEFAULT 0,
            status TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )",
        
        "cards" => "CREATE TABLE IF NOT EXISTS cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            card_number VARCHAR(255) NOT NULL,
            card_password VARCHAR(255),
            status TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )",
        
        "orders" => "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_no VARCHAR(50) UNIQUE NOT NULL,
            product_id INT,
            product_name VARCHAR(200),
            price DECIMAL(10,2),
            contact_info VARCHAR(255),
            status TINYINT DEFAULT 0,
            pay_method VARCHAR(50),
            card_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            paid_at TIMESTAMP NULL,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
            FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL
        )",
        
        "admin_logs" => "CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $table_name => $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("创建表 {$table_name} 失败: " . $e->getMessage());
        }
    }
}


function get_site_stats() {
    global $pdo;
    $stats = [
        'total_orders' => 0,
        'total_products' => 0,
        'total_revenue' => 0,
        'today_orders' => 0
    ];
    
    try {
        // 总订单数
        $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
        $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
        
        // 总商品数
        $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products WHERE status = 1");
        $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
        
        // 总销售额（只计算已支付订单，使用 final_amount）
        $stmt = $pdo->query("SELECT SUM(final_amount) as total_revenue FROM orders WHERE status = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_revenue'] = $result['total_revenue'] ?: 0;
        
        // 今日订单
        $stmt = $pdo->query("SELECT COUNT(*) as today_orders FROM orders WHERE DATE(created_at) = CURDATE()");
        $stats['today_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_orders'];
        
    } catch (PDOException $e) {
        error_log("获取统计信息失败: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * 获取分类销售统计
 */
function getCategorySalesStats($category_id = null) {
    global $pdo;
    
    try {
        $where = '';
        $params = [];
        
        if ($category_id) {
            $where = "WHERE c.id = ?";
            $params[] = $category_id;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.name,
                COALESCE(SUM(CASE WHEN DATE(o.paid_at) = CURDATE() THEN o.price ELSE 0 END), 0) as today_sales,
                COALESCE(SUM(CASE WHEN o.status = 1 THEN o.price ELSE 0 END), 0) as total_sales,
                COUNT(CASE WHEN DATE(o.paid_at) = CURDATE() THEN o.id END) as today_orders,
                COUNT(CASE WHEN o.status = 1 THEN o.id END) as total_orders
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            LEFT JOIN orders o ON p.id = o.product_id AND o.status = 1
            {$where}
            GROUP BY c.id, c.name
            ORDER BY today_sales DESC, total_sales DESC
        ");
        
        $stmt->execute($params);
        
        if ($category_id) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } catch (PDOException $e) {
        error_log("获取分类销售统计失败: " . $e->getMessage());
        return $category_id ? [] : [];
    }
}

/**
 * 获取所有分类的销售统计汇总
 */
function getCategorySalesSummary() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT c.id) as category_count,
                COALESCE(SUM(CASE WHEN DATE(o.paid_at) = CURDATE() THEN o.price ELSE 0 END), 0) as total_today_sales,
                COALESCE(SUM(CASE WHEN o.status = 1 THEN o.price ELSE 0 END), 0) as total_all_sales,
                COUNT(CASE WHEN DATE(o.paid_at) = CURDATE() THEN o.id END) as total_today_orders,
                COUNT(CASE WHEN o.status = 1 THEN o.id END) as total_all_orders
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            LEFT JOIN orders o ON p.id = o.product_id AND o.status = 1
            WHERE c.status = 1
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("获取分类销售汇总失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取分类销售趋势（最近7天）
 */
function getCategorySalesTrend($category_id, $days = 7) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(o.paid_at) as sale_date,
                COALESCE(SUM(o.price), 0) as daily_sales,
                COUNT(o.id) as daily_orders
            FROM orders o
            LEFT JOIN products p ON o.product_id = p.id
            WHERE p.category_id = ? 
            AND o.status = 1 
            AND o.paid_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(o.paid_at)
            ORDER BY sale_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$category_id, $days, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("获取分类销售趋势失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取热门商品
 */
function getHotProducts($limit = 6) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              WHERE p.status = 1 AND p.stock > 0 
                              ORDER BY p.id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取热门商品失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 显示成功消息
 */
function show_success($message) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>' . $message . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

/**
 * 显示错误消息
 */
function show_error($message) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>' . $message . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

/**
 * 检查管理员登录状态
 */
function check_admin_login() {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: admin/login.php');
        exit;
    }
}

/**
 * 重定向函数
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

function getProductStock($product_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as stock FROM cards WHERE product_id = ? AND status = 0");
        $stmt->execute([$product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['stock'] ?: 0;
    } catch (PDOException $e) {
        error_log("获取商品库存失败: " . $e->getMessage());
        return 0;
    }
}

/**
 * 检查商品是否可以购买（有库存且启用）
 */
function isProductAvailable($product_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND status = 0) as stock
            FROM products p 
            WHERE p.id = ? AND p.status = 1
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $product && $product['stock'] > 0;
    } catch (PDOException $e) {
        error_log("检查商品可用性失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 获取商品详情（包含库存信息）
 */
function getProductWithStock($product_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name,
                   (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND status = 0) as stock
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取商品详情失败: " . $e->getMessage());
        return false;
    }
}

function getCouponByCode($code) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM coupons 
            WHERE code = ? 
            AND status = 1 
            AND (start_date IS NULL OR start_date <= CURDATE())
            AND (end_date IS NULL OR end_date >= CURDATE())
            AND (usage_limit = 0 OR used_count < usage_limit)
        ");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取优惠券失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 验证优惠券是否可用
 */
function validateCoupon($code, $amount, $category_id = null) {
    $coupon = getCouponByCode($code);
    
    if (!$coupon) {
        return ['valid' => false, 'message' => '优惠券不存在或已失效'];
    }
    
    // 检查最低金额要求
    if ($coupon['min_amount'] > 0 && $amount < $coupon['min_amount']) {
        return ['valid' => false, 'message' => '订单金额不满足优惠券使用条件'];
    }
    
    // 检查适用分类
    if (!empty($coupon['applicable_categories'])) {
        $applicable_categories = explode(',', $coupon['applicable_categories']);
        if (!in_array($category_id, $applicable_categories)) {
            return ['valid' => false, 'message' => '该优惠券不适用于此分类商品'];
        }
    }
    
    // 计算优惠金额
    $discount_amount = 0;
    if ($coupon['type'] == 'fixed') {
        $discount_amount = $coupon['value'];
    } elseif ($coupon['type'] == 'percent') {
        $discount_amount = $amount * ($coupon['value'] / 100);
        if ($coupon['max_discount'] > 0 && $discount_amount > $coupon['max_discount']) {
            $discount_amount = $coupon['max_discount'];
        }
    }
    
    $final_amount = max(0, $amount - $discount_amount);
    
    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount_amount' => $discount_amount,
        'final_amount' => $final_amount
    ];
}

/**
 * 使用优惠券
 */
function useCoupon($coupon_id, $order_no, $user_contact, $discount_amount) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 记录使用记录
        $stmt = $pdo->prepare("INSERT INTO coupon_usage (coupon_id, order_no, user_contact, discount_amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$coupon_id, $order_no, $user_contact, $discount_amount]);
        
        // 更新使用次数
        $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$coupon_id]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("使用优惠券失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 更新分类余额
 */
function updateCategoryBalance($category_id, $amount) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE categories SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $category_id]);
        return true;
    } catch (PDOException $e) {
        error_log("更新分类余额失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 获取分类余额
 */
function getCategoryBalance($category_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT balance FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['balance'] ?: 0;
    } catch (PDOException $e) {
        error_log("获取分类余额失败: " . $e->getMessage());
        return 0;
    }
}

/**
 * 处理0元订单（支持数量）
 */
/**
 * 处理0元订单（直接发放卡密）- 支持数量
 */
function handleZeroAmountOrder($order_no, $quantity = 1) {
    global $pdo;
    
    try {
        // 获取订单信息
        $stmt = $pdo->prepare("SELECT o.*, p.category_id FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE o.order_no = ?");
        $stmt->execute([$order_no]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 为每个数量分配卡密
        for ($i = 0; $i < $quantity; $i++) {
            // 获取可用卡密
            $card = getAvailableCard($order['product_id']);
            if (!$card) {
                throw new Exception("卡密不足，需要 {$quantity} 个，但只有 {$i} 个可用");
            }
            
            // 更新订单状态为已支付（只在第一次循环时更新主订单）
            if ($i === 0) {
                $stmt = $pdo->prepare("UPDATE orders SET status = 1, paid_at = NOW() WHERE order_no = ?");
                $stmt->execute([$order_no]);
            }
            
            // 标记卡密为已售
            $stmt = $pdo->prepare("UPDATE cards SET status = 1 WHERE id = ?");
            $stmt->execute([$card['id']]);
            
            // 记录订单卡密关系（需要创建order_cards表）
            $stmt = $pdo->prepare("INSERT INTO order_cards (order_no, card_id, card_number, card_password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_no, $card['id'], $card['card_number'], $card['card_password']]);
        }
        
        // 提交事务
        $pdo->commit();
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("处理0元订单失败: " . $e->getMessage());
        return false;
    }
}



/**
 * 处理支付成功（更新余额并发送邮件）
 */
function handlePaymentSuccess($order_no, $pay_method) {
    global $pdo;
    
    try {
        // 获取订单信息
        $stmt = $pdo->prepare("SELECT o.*, p.category_id FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE o.order_no = ? AND o.status = 0");
        $stmt->execute([$order_no]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            error_log("支付成功处理: 订单不存在或已处理 - " . $order_no);
            return false;
        }
        
        $quantity = $order['quantity'] ?? 1;
        $cards = [];
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 为每个数量分配卡密
        for ($i = 0; $i < $quantity; $i++) {
            // 获取可用卡密
            $card = getAvailableCard($order['product_id']);
            if (!$card) {
                throw new Exception("卡密不足，需要 {$quantity} 个，但只有 {$i} 个可用");
            }
            
            // 只在第一次循环时更新主订单状态
            if ($i === 0) {
                $stmt = $pdo->prepare("UPDATE orders SET status = 1, pay_method = ?, paid_at = NOW() WHERE order_no = ?");
                $stmt->execute([$pay_method, $order_no]);
            }
            
            // 标记卡密为已售
            $stmt = $pdo->prepare("UPDATE cards SET status = 1 WHERE id = ?");
            $stmt->execute([$card['id']]);
            
            // 记录到订单卡密表
            $stmt = $pdo->prepare("INSERT INTO order_cards (order_no, card_id, card_number, card_password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_no, $card['id'], $card['card_number'], $card['card_password']]);
            
            $cards[] = [
                'card_number' => $card['card_number'],
                'card_password' => $card['card_password']
            ];
        }
        
        // 更新分类余额 - 扣除千分之6费率
        if ($order['category_id']) {
            $final_amount = $order['final_amount'];
            $fee_amount = $final_amount * TRANSACTION_FEE_RATE;
            $net_amount = $final_amount - $fee_amount;
            
            updateCategoryBalance($order['category_id'], $net_amount);
            
            error_log("支付成功 - 更新分类余额: 分类ID {$order['category_id']}, 金额 {$final_amount}, 费率 {$fee_amount}, 净额 {$net_amount}");
        } else {
            error_log("支付成功 - 警告: 订单 {$order_no} 没有分类ID，无法更新余额");
        }
        
        // 提交事务
        $pdo->commit();
        
        // 发送邮件通知
        if (EMAIL_NOTIFY_ENABLED && EMAIL_NOTIFY_PAYMENT) {
            require_once 'mailer.php';
            global $mailer;
            
            $order_info = [
                'order_no' => $order['order_no'],
                'product_name' => $order['product_name'],
                'price' => $order['price'],
                'quantity' => $quantity,
                'discount_amount' => $order['discount_amount'] ?: 0,
                'final_amount' => $final_amount,
                'paid_at' => date('Y-m-d H:i:s')
            ];
            
            // 发送邮件到客户邮箱
            if (!empty($order['contact_info'])) {
                if (filter_var($order['contact_info'], FILTER_VALIDATE_EMAIL)) {
                    $mailer->sendPaymentSuccessEmail($order['contact_info'], $order_info, $cards);
                    error_log("支付成功邮件已发送至: " . $order['contact_info'] . "，数量: " . $quantity);
                }
            }
        }
        
        error_log("支付成功处理完成 - 订单号: " . $order_no . ", 数量: " . $quantity . ", 金额: " . $final_amount . ", 费率扣除: " . $fee_amount);
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("处理支付成功失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 计算扣除费率后的金额
 */
function calculateNetAmount($amount) {
    $fee_rate = defined('TRANSACTION_FEE_RATE') ? TRANSACTION_FEE_RATE : 0.006;
    $fee_amount = $amount * $fee_rate;
    $net_amount = $amount - $fee_amount;
    
    return [
        'original_amount' => $amount,
        'fee_rate' => $fee_rate,
        'fee_amount' => $fee_amount,
        'net_amount' => $net_amount
    ];
}

/**
 * 获取费率信息
 */
function getFeeInfo() {
    $fee_rate = defined('TRANSACTION_FEE_RATE') ? TRANSACTION_FEE_RATE : 0.006;
    return [
        'rate' => $fee_rate,
        'rate_percent' => $fee_rate * 100,
        'net_percent' => (1 - $fee_rate) * 100
    ];
}

// 在文件加载时自动创建表
create_tables_if_not_exist();
?>