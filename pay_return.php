<?php
require_once 'includes/functions.php';
require_once 'includes/epay.php';

// 验证签名
if (verifyEpayNotify()) {
    $order_no = $_GET['out_trade_no'];
    $trade_status = $_GET['trade_status'];
    
    // 获取订单信息（包含数量）
    $stmt = $pdo->prepare("SELECT o.*, p.category_id FROM orders o 
                          LEFT JOIN products p ON o.product_id = p.id 
                          WHERE o.order_no = ?");
    $stmt->execute([$order_no]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $success = ($trade_status === 'TRADE_SUCCESS' && $order && $order['status'] == 1);
    
    if ($success) {
        // 获取订单的所有卡密（支持批量购买）
        $cards_stmt = $pdo->prepare("SELECT * FROM order_cards WHERE order_no = ?");
        $cards_stmt->execute([$order_no]);
        $cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $success = false;
    $error = '签名验证失败';
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

                        <!-- 卡密信息卡片 -->
                        <?php if (!empty($cards)): ?>
                        <div class="card-info">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0"><i class="fas fa-key"></i> 卡密信息</h5>
                                <span class="card-count">共 <?php echo count($cards); ?> 个卡密</span>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- 合并显示所有卡密，一行一个 -->
                                    <div class="card-text" id="allCardsText">
<?php
// 合并所有卡密，一行一个
$all_cards_text = "";
foreach ($cards as $index => $card) {
    $card_number = $card['card_number'];
    $card_password = $card['card_password'];
    
    if ($card_password) {
        $all_cards_text .= "卡号：{$card_number} 密码：{$card_password}\n";
    } else {
        $all_cards_text .= "卡号：{$card_number}\n";
    }
}
echo htmlspecialchars(trim($all_cards_text));
?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-grid gap-2">
                                        <button class="btn copy-btn" onclick="copyAllCards()">
                                            <i class="fas fa-copy"></i> 复制所有卡密
                                        </button>
                                        <button class="btn copy-btn" onclick="copyCardNumbers()">
                                            <i class="fas fa-copy"></i> 复制所有卡号
                                        </button>
                                        <?php 
                                        $has_passwords = false;
                                        foreach ($cards as $card) {
                                            if (!empty($card['card_password'])) {
                                                $has_passwords = true;
                                                break;
                                            }
                                        }
                                        if ($has_passwords): ?>
                                        <button class="btn copy-btn" onclick="copyCardPasswords()">
                                            <i class="fas fa-copy"></i> 复制所有密码
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning mt-3 mb-0" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>重要提示：</strong> 请及时复制保存卡密信息，关闭页面后将无法再次查看！
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 未找到卡密信息，请联系客服
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> 支付失败</h5>
                            <p><?php echo $error ?? '支付未完成或验证失败'; ?></p>
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
            const text = document.getElementById('allCardsText').innerText;
            copyToClipboard(text, '所有卡密复制成功！');
        }

        // 复制所有卡号
        function copyCardNumbers() {
            const allText = document.getElementById('allCardsText').innerText;
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
            const allText = document.getElementById('allCardsText').innerText;
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
                // 创建选择范围
                const range = document.createRange();
                range.selectNodeContents(cardsText);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
            }
        });
    </script>
</body>
</html>