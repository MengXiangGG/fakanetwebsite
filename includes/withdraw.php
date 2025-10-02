<?php
require_once 'config.php';

/**
 * 记录单笔提现申请
 */
function addWithdrawApplication($category_id, $amount, $applicant_name = '系统', $contact = '', $account = '', $method = 'alipay') {
    global $pdo;
    
    try {
        // 获取分类信息
        $stmt = $pdo->prepare("SELECT name, balance FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            throw new Exception("分类不存在");
        }
        
        // 验证余额是否足够
        if ($category['balance'] < $amount) {
            throw new Exception("分类余额不足，当前余额: {$category['balance']}，申请金额: {$amount}");
        }
        
        // 计算该分类的订单数量
        $order_stmt = $pdo->prepare("
            SELECT COUNT(o.id) as order_count 
            FROM orders o 
            LEFT JOIN products p ON o.product_id = p.id 
            WHERE p.category_id = ? 
            AND o.status = 1 
            AND DATE(o.paid_at) = CURDATE()
        ");
        $order_stmt->execute([$category_id]);
        $order_result = $order_stmt->fetch(PDO::FETCH_ASSOC);
        $order_count = $order_result['order_count'] ?: 0;
        
        $withdraw_no = 'WD' . date('YmdHis') . str_pad($category_id, 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("
            INSERT INTO withdrawals (
                withdraw_no, category_id, category_name, amount, order_count,
                applicant_name, applicant_contact, withdraw_account, withdraw_method,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $withdraw_no,
            $category_id,
            $category['name'],
            $amount,
            $order_count,
            $applicant_name,
            $contact,
            $account,
            $method
        ]);
        
        return $withdraw_no;
        
    } catch (PDOException $e) {
        error_log("记录提现申请失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 自动提现功能 - 基于分类余额，生成提现申请后立即锁定余额
 */
function autoWithdrawByCategory() {
    global $pdo;
    
    try {
        // 获取所有有余额的分类
        $categories = $pdo->query("SELECT * FROM categories WHERE status = 1 AND balance > 0")->fetchAll(PDO::FETCH_ASSOC);
        
        $withdraw_results = [];
        
        foreach ($categories as $category) {
            $balance = $category['balance'];
            
            if ($balance > 0) {
                // 开始事务
                $pdo->beginTransaction();
                
                try {
                    // 计算该分类今日订单数量
                    $order_stmt = $pdo->prepare("
                        SELECT COUNT(o.id) as order_count 
                        FROM orders o 
                        LEFT JOIN products p ON o.product_id = p.id 
                        WHERE p.category_id = ? 
                        AND o.status = 1 
                        AND DATE(o.paid_at) = CURDATE()
                    ");
                    $order_stmt->execute([$category['id']]);
                    $order_result = $order_stmt->fetch(PDO::FETCH_ASSOC);
                    $order_count = $order_result['order_count'] ?: 0;
                    
                    // 扣除千分之6费率后的可提现金额
                    $fee_amount = $balance * TRANSACTION_FEE_RATE;
                    $withdraw_amount = $balance - $fee_amount;
                    
                    // 为每个有余额的分类创建提现申请
                    $withdraw_no = addWithdrawApplication(
                        $category['id'], 
                        $withdraw_amount, 
                        '系统', 
                        '', 
                        '', 
                        'alipay'
                    );
                    
                    if ($withdraw_no) {
                        // 关键修复：生成提现申请后立即清零余额，防止重复提现
                        $stmt = $pdo->prepare("UPDATE categories SET balance = 0 WHERE id = ?");
                        $stmt->execute([$category['id']]);
                        
                        $pdo->commit();
                        
                        $withdraw_results[] = [
                            'category' => $category['name'],
                            'original_balance' => $balance,
                            'fee_amount' => $fee_amount,
                            'withdraw_amount' => $withdraw_amount,
                            'orders' => $order_count,
                            'withdraw_no' => $withdraw_no
                        ];
                        
                        error_log("自动提现申请 - 分类: {$category['name']}, 原余额: {$balance}, 费率扣除: {$fee_amount}, 可提现: {$withdraw_amount}, 订单数: {$order_count}");
                    } else {
                        $pdo->rollBack();
                    }
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("自动提现分类 {$category['name']} 失败: " . $e->getMessage());
                }
            }
        }
        
        return $withdraw_results;
        
    } catch (PDOException $e) {
        error_log("自动提现失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 获取提现记录（带分页）- 重命名避免冲突
 */
function getWithdrawalRecords($page = 1, $per_page = 20, $status = '') {
    global $pdo;
    
    try {
        $offset = ($page - 1) * $per_page;
        $where = '';
        $params = [];
        
        if ($status && in_array($status, ['pending', 'processed', 'failed'])) {
            $where = "WHERE status = ?";
            $params[] = $status;
        }
        
        $sql = "
            SELECT * FROM withdrawals 
            {$where}
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $pdo->prepare($sql);
        
        // 正确绑定参数
        $param_count = 0;
        if ($status) {
            $stmt->bindValue(++$param_count, $status, PDO::PARAM_STR);
        }
        $stmt->bindValue(++$param_count, $per_page, PDO::PARAM_INT);
        $stmt->bindValue(++$param_count, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取提现记录失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取提现统计
 */
function getWithdrawStats() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'processed' THEN amount ELSE 0 END) as processed_amount,
                SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed_count,
                SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed_amount,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
            FROM withdrawals
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取提现统计失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 审批提现申请
 */
function processWithdraw($withdraw_id, $action, $admin_notes = '', $approved_amount = null, $processed_by = '管理员') {
    global $pdo;
    
    try {
        // 获取原始提现记录
        $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
        $stmt->execute([$withdraw_id]);
        $withdraw = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$withdraw) {
            throw new Exception("提现记录不存在");
        }
        
        if ($withdraw['status'] !== 'pending') {
            throw new Exception("该提现申请已处理");
        }
        
        $new_status = $action === 'approve' ? 'processed' : 'failed';
        $approved_amount = $approved_amount ?: $withdraw['amount'];
        
        $stmt = $pdo->prepare("
            UPDATE withdrawals 
            SET status = ?, 
                approved_amount = ?,
                admin_notes = ?,
                processed_by = ?,
                processed_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $new_status,
            $approved_amount,
            $admin_notes,
            $processed_by,
            $withdraw_id
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("审批提现失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 获取今日收入统计
 */
function getTodayIncome() {
    global $pdo;
    
    try {
        // 按分类统计今日收入
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, 
                   COALESCE(SUM(o.final_amount), 0) as total_amount,
                   COUNT(o.id) as order_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            LEFT JOIN orders o ON p.id = o.product_id AND o.status = 1 AND DATE(o.paid_at) = CURDATE()
            WHERE c.status = 1
            GROUP BY c.id, c.name
            ORDER BY total_amount DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取今日收入统计失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取待审批提现数量
 */
function getPendingWithdrawCount() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM withdrawals WHERE status = 'pending'");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        error_log("获取待审批数量失败: " . $e->getMessage());
        return 0;
    }
}
?>