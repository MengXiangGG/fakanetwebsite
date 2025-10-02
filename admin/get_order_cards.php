<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die('Access Denied');
}

require_once '../includes/functions.php';

$order_no = isset($_GET['order_no']) ? $_GET['order_no'] : '';

if (empty($order_no)) {
    die('参数错误');
}

// 获取订单的所有卡密
$cards_stmt = $pdo->prepare("SELECT * FROM order_cards WHERE order_no = ? ORDER BY id");
$cards_stmt->execute([$order_no]);
$cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cards)) {
    echo '<div class="alert alert-info">暂无卡密信息</div>';
    exit;
}

// 生成整合的卡密文本
$all_cards_text = "";
foreach ($cards as $card) {
    if ($card['card_password']) {
        $all_cards_text .= "卡号：{$card['card_number']} 密码：{$card['card_password']}\n";
    } else {
        $all_cards_text .= "卡号：{$card['card_number']}\n";
    }
}
?>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">卡密详情 - 共 <?php echo count($cards); ?> 个卡密</h6>
    </div>
    <div class="card-body">
        <!-- 整合的卡密文本区域 -->
        <div class="mb-4">
            <label class="form-label fw-bold">整合卡密（一键复制）</label>
            <textarea class="form-control" id="allCardsText" rows="8" readonly style="font-family: 'Courier New', monospace; font-size: 14px;"><?php echo htmlspecialchars(trim($all_cards_text)); ?></textarea>
            <div class="mt-2">
                <button class="btn btn-success btn-sm" onclick="copyAllCards()">
                    <i class="fas fa-copy me-1"></i>复制所有卡密
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="copyCardNumbers()">
                    <i class="fas fa-copy me-1"></i>仅复制卡号
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
                <button class="btn btn-outline-info btn-sm" onclick="copyCardPasswords()">
                    <i class="fas fa-copy me-1"></i>仅复制密码
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 详细的卡密表格 -->
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>卡号</th>
                        <th>密码</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cards as $index => $card): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <code class="card-number"><?php echo htmlspecialchars($card['card_number']); ?></code>
                        </td>
                        <td>
                            <?php if ($card['card_password']): ?>
                            <code class="card-password"><?php echo htmlspecialchars($card['card_password']); ?></code>
                            <?php else: ?>
                            <span class="text-muted">无密码</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-outline-primary btn-sm" onclick="copyText('<?php echo $card['card_number']; ?>')">
                                复制卡号
                            </button>
                            <?php if ($card['card_password']): ?>
                            <button class="btn btn-outline-secondary btn-sm" onclick="copyText('<?php echo $card['card_password']; ?>')">
                                复制密码
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// 复制单个文本
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        showCopySuccess('复制成功！');
    }).catch(err => {
        // 降级方案
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showCopySuccess('复制成功！');
    });
}

// 复制所有卡密
function copyAllCards() {
    const text = document.getElementById('allCardsText').value;
    copyText(text);
}

// 仅复制卡号
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
    
    copyText(cardNumbers.trim());
}

// 仅复制密码
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
    
    copyText(passwords.trim());
}

// 显示复制成功提示
function showCopySuccess(message) {
    // 移除现有的提示
    const existingToast = document.querySelector('.copy-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // 创建提示元素
    const toast = document.createElement('div');
    toast.className = 'copy-toast alert alert-success position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 200px;';
    toast.innerHTML = `<i class="fas fa-check-circle me-2"></i>${message}`;
    document.body.appendChild(toast);
    
    // 3秒后自动移除
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// 页面加载后自动选中所有卡密文本
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

<style>
.card-number, .card-password {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #e9ecef;
}
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