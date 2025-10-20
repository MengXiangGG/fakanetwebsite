<?php
require_once 'includes/functions.php';
require_once 'includes/epay.php';

// 记录回调请求
error_log("=== 收到支付返回回调 ===");
error_log("GET参数: " . print_r($_GET, true));

// 验证签名
$verify_result = verifyEpayNotify();

if ($verify_result) {
    $order_no = $_GET['out_trade_no'];
    $trade_status = $_GET['trade_status'];
    
    error_log("回调验证成功 - 订单号: {$order_no}, 状态: {$trade_status}");
    
    if ($trade_status === 'TRADE_SUCCESS') {
        // 获取订单信息（包含数量）
        $stmt = $pdo->prepare("SELECT o.*, p.category_id FROM orders o 
                              LEFT JOIN products p ON o.product_id = p.id 
                              WHERE o.order_no = ?");
        $stmt->execute([$order_no]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success = ($order && $order['status'] == 1);
        
        if ($success) {
            // 获取订单的所有卡密（支持批量购买）
            $cards_stmt = $pdo->prepare("SELECT * FROM order_cards WHERE order_no = ?");
            $cards_stmt->execute([$order_no]);
            $cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $success = false;
        $error = '支付状态不是成功';
    }
} else {
    $success = false;
    $error = '签名验证失败';
    
    // 调试信息
    error_log("支付返回签名验证失败");
    error_log("GET参数: " . print_r($_GET, true));
    
    // 即使签名验证失败，也尝试显示订单信息（为了用户体验）
    $order_no = $_GET['out_trade_no'] ?? '';
    if ($order_no) {
        $stmt = $pdo->prepare("SELECT o.*, p.category_id FROM orders o 
                              LEFT JOIN products p ON o.product_id = p.id 
                              WHERE o.order_no = ?");
        $stmt->execute([$order_no]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order && $order['status'] == 1) {
            $success = true;
            $cards_stmt = $pdo->prepare("SELECT * FROM order_cards WHERE order_no = ?");
            $cards_stmt->execute([$order_no]);
            $cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);
            $error = '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支付结果 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card-text {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .copy-btn {
            background: rgba(255,255,255,0.3);
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            transition: all 0.3s;
            padding: 10px 20px;
            font-size: 1rem;
        }
        .copy-btn:hover {
            background: rgba(255,255,255,0.5);
            transform: translateY(-2px);
        }
        .card-count {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">支付结果</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> 支付成功！</h5>
                            <p>感谢您的购买，请妥善保存卡密信息</p>
                        </div>
                        
                        <!-- 订单信息 -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">订单信息</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>订单号：</strong> <?php echo $order['order_no']; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>商品名称：</strong> <?php echo htmlspecialchars($order['product_name']); ?>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong>购买数量：</strong> 
                                        <span class="badge bg-primary"><?php echo $order['quantity']; ?> 件</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>商品单价：</strong> ¥<?php echo number_format($order['price'], 2); ?>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong>优惠金额：</strong> 
                                        <span class="text-success">-¥<?php echo number_format($order['discount_amount'], 2); ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>支付金额：</strong> 
                                        <span class="text-danger fw-bold">¥<?php echo number_format($order['final_amount'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <strong>支付时间：</strong> <?php echo $order['paid_at']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

<!-- 卡密信息 -->
<?php if (!empty($cards)): ?>
<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h5 class="mb-0"><i class="fas fa-key me-2"></i>卡密信息</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>重要提示：</strong>请立即复制并妥善保存以下卡密信息，页面关闭后将无法再次查看！
        </div>
        
        <!-- 整合的卡密文本 -->
        <div class="mb-4">
            <label class="form-label fw-bold">整合卡密（一键复制）</label>
            <textarea class="form-control" id="allCardsText" rows="8" readonly style="font-family: 'Courier New', monospace; font-size: 14px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px;"><?php 
// 生成整合的卡密文本 - 去除空格
$all_cards_text = "";
foreach ($cards as $card) {
    // 去除卡号和密码中的前后空格
    $card_number = trim($card['card_number']);
    $card_password = trim($card['card_password']);
    
    if ($card_password) {
        $all_cards_text .= "卡号：{$card_number} 密码：{$card_password}\n";
    } else {
        $all_cards_text .= "卡号：{$card_number}\n";
    }
}
echo htmlspecialchars(trim($all_cards_text));
?></textarea>
            <div class="mt-2">
                <button class="btn btn-success btn-sm" onclick="copyAllCards()" style="background: #10a37f; border-color: #10a37f;">
                    <i class="fas fa-copy me-1"></i>复制所有卡密
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="copyCardNumbers()">
                    <i class="fas fa-copy me-1"></i>仅复制卡号
                </button>
                <?php 
                $has_passwords = false;
                foreach ($cards as $card) {
                    if (!empty(trim($card['card_password']))) {
                        $has_passwords = true;
                        break;
                    }
                }
                if ($has_passwords): ?>
                <button class="btn btn-outline-info btn-sm" onclick="copyCardPasswords()">
                    <i class="fas fa-copy me-1"></i>仅复制密码
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    暂无卡密信息，请联系客服处理。
</div>
<?php endif; ?>

                        <?php else: ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> 支付失败</h5>
                            <p><?php echo $error ?? '支付未完成或验证失败'; ?></p>
                            <?php if (isset($order_no)): ?>
                            <p class="mb-0">订单号: <?php echo $order_no; ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <?php if ($success && isset($order['category_id'])): ?>
                            <a href="category.php?id=<?php echo $order['category_id']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-cart"></i> 继续购物
                            </a>
                            <?php endif; ?>
                            <button class="btn btn-success" onclick="window.print()">
                                <i class="fas fa-print"></i> 打印卡密
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    // 复制所有卡密
    function copyAllCards() {
        const text = document.getElementById('allCardsText').value;
        copyToClipboard(text, '所有卡密复制成功！');
    }

    // 复制所有卡号
    function copyCardNumbers() {
        const allText = document.getElementById('allCardsText').value;
        const lines = allText.split('\n');
        let cardNumbers = '';
        
        lines.forEach(line => {
            if (line.includes('卡号：')) {
                const cardNumber = line.split('卡号：')[1].split('密码：')[0].trim();
                cardNumbers += cardNumber + '\n';
            }
        });
        
        copyToClipboard(cardNumbers.trim(), '所有卡号复制成功！');
    }

    // 复制所有密码
    function copyCardPasswords() {
        const allText = document.getElementById('allCardsText').value;
        const lines = allText.split('\n');
        let passwords = '';
        
        lines.forEach(line => {
            if (line.includes('密码：')) {
                const password = line.split('密码：')[1].trim();
                passwords += password + '\n';
            }
        });
        
        copyToClipboard(passwords.trim(), '所有密码复制成功！');
    }

    // 复制到剪贴板
    function copyToClipboard(text, successMessage) {
        // 使用现代API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showCopySuccess(successMessage);
            }).catch(err => {
                fallbackCopyText(text, successMessage);
            });
        } else {
            // 降级方案
            fallbackCopyText(text, successMessage);
        }
    }

    // 降级复制方案
    function fallbackCopyText(text, successMessage) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            if (successful) {
                showCopySuccess(successMessage);
            } else {
                showCopyError('复制失败，请手动复制');
            }
        } catch (err) {
            document.body.removeChild(textArea);
            showCopyError('复制失败，请手动复制');
        }
    }

    function showCopySuccess(message) {
        showMessage(message, 'success');
    }

    function showCopyError(message) {
        showMessage(message, 'danger');
    }

    function showMessage(message, type) {
        // 移除现有的提示
        const existingToast = document.querySelector('.copy-toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        // 创建提示元素
        const toast = document.createElement('div');
        toast.className = `copy-toast alert alert-${type} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
            ${message}
        `;
        document.body.appendChild(toast);
        
        // 3秒后自动移除
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // 页面加载后自动选中卡密文本，方便用户直接复制
    document.addEventListener('DOMContentLoaded', function() {
        const cardsText = document.getElementById('allCardsText');
        if (cardsText) {
            cardsText.focus();
            cardsText.select();
        }
    });
</script>

<style>
.copy-toast {
    animation: fadeInOut 3s ease-in-out;
}
@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(-20px); }
    20% { opacity: 1; transform: translateY(0); }
    80% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-20px); }
}
</style>


</body>
</html>