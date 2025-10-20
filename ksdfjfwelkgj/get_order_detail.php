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

// 获取订单详情
$stmt = $pdo->prepare("
    SELECT o.*, c.name as category_name, p.description as product_description 
    FROM orders o 
    LEFT JOIN products p ON o.product_id = p.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE o.order_no = ?
");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<div class="alert alert-danger">订单不存在</div>';
    exit;
}

// 获取订单的所有卡密
$cards_stmt = $pdo->prepare("SELECT * FROM order_cards WHERE order_no = ? ORDER BY id");
$cards_stmt->execute([$order_no]);
$cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);

// 计算费率信息
$fee_info = calculateNetAmount($order['final_amount']);
?>

<div class="row">
    <div class="col-md-6">
        <h6>订单基本信息</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <td width="120" class="bg-light"><strong>订单号:</strong></td>
                <td><?php echo $order['order_no']; ?></td>
            </tr>
            <tr>
                <td class="bg-light"><strong>商品名称:</strong></td>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
            </tr>
            <tr>
                <td class="bg-light"><strong>商品分类:</strong></td>
                <td><?php echo htmlspecialchars($order['category_name']); ?></td>
            </tr>
            <tr>
                <td class="bg-light"><strong>购买数量:</strong></td>
                <td><span class="badge bg-primary"><?php echo $order['quantity']; ?> 件</span></td>
            </tr>
            <tr>
                <td class="bg-light"><strong>联系方式:</strong></td>
                <td><?php echo htmlspecialchars($order['contact_info']); ?></td>
            </tr>
            <tr>
                <td class="bg-light"><strong>优惠券:</strong></td>
                <td>
                    <?php if ($order['coupon_code']): ?>
                    <span class="badge bg-success"><?php echo $order['coupon_code']; ?></span>
                    <?php else: ?>
                    <span class="text-muted">未使用</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>金额信息</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <td width="120" class="bg-light"><strong>商品单价:</strong></td>
                <td>¥<?php echo number_format($order['price'], 2); ?></td>
            </tr>
            <tr>
                <td class="bg-light"><strong>购买数量:</strong></td>
                <td><?php echo $order['quantity']; ?> 件</td>
            </tr>
            <tr>
                <td class="bg-light"><strong>小计金额:</strong></td>
                <td>¥<?php echo number_format($order['price'] * $order['quantity'], 2); ?></td>
            </tr>
            <?php if ($order['discount_amount'] > 0): ?>
            <tr>
                <td class="bg-light"><strong>优惠金额:</strong></td>
                <td class="text-success">-¥<?php echo number_format($order['discount_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="bg-light"><strong>实付金额:</strong></td>
                <td class="fw-bold text-danger">¥<?php echo number_format($order['final_amount'], 2); ?></td>
            </tr>
            <?php if ($order['status'] == 1): ?>
            <tr>
                <td class="bg-light"><strong>交易费率:</strong></td>
                <td class="text-warning">-¥<?php echo number_format($fee_info['fee_amount'], 2); ?> (<?php echo $fee_info['rate_percent']; ?>%)</td>
            </tr>
            <tr>
                <td class="bg-light"><strong>实际到账:</strong></td>
                <td class="fw-bold text-success">¥<?php echo number_format($fee_info['net_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <h6>时间信息</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <td width="120" class="bg-light"><strong>创建时间:</strong></td>
                <td><?php echo $order['created_at']; ?></td>
            </tr>
            <tr>
                <td class="bg-light"><strong>支付时间:</strong></td>
                <td><?php echo $order['paid_at'] ?: '<span class="text-muted">未支付</span>'; ?></td>
            </tr>
            <tr>
                <td class="bg-light"><strong>支付方式:</strong></td>
                <td><?php echo $order['pay_method'] ?: '<span class="text-muted">未支付</span>'; ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>状态信息</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <td width="120" class="bg-light"><strong>订单状态:</strong></td>
                <td>
                    <span class="badge bg-<?php 
                        switch($order['status']) {
                            case 0: echo 'warning'; break;
                            case 1: echo 'success'; break;
                            case 2: echo 'info'; break;
                            case 3: echo 'danger'; break;
                        }
                    ?>">
                        <?php 
                        switch($order['status']) {
                            case 0: echo '待支付'; break;
                            case 1: echo '已支付'; break;
                            case 2: echo '已完成'; break;
                            case 3: echo '已取消'; break;
                        }
                        ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="bg-light"><strong>卡密状态:</strong></td>
                <td>
                    <?php if (!empty($cards)): ?>
                    <span class="badge bg-success">已发放 (<?php echo count($cards); ?>个)</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">未发放</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($cards)): ?>
<div class="row mt-4">
    <div class="col-12">
        <h6>卡密信息 (共 <?php echo count($cards); ?> 个)</h6>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
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
                            <button class="btn btn-sm btn-outline-primary" onclick="copyText('<?php echo $card['card_number']; ?>')">
                                复制卡号
                            </button>
                            <?php if ($card['card_password']): ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="copyText('<?php echo $card['card_password']; ?>')">
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
<?php endif; ?>

<script>
// 复制文本功能
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