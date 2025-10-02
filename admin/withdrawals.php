<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';
require_once '../includes/withdraw.php';

// 处理审批操作
if (isset($_POST['action'])) {
    $withdraw_id = intval($_POST['withdraw_id']);
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    $approved_amount = $_POST['approved_amount'] ?? null;
    
    if (processWithdraw($withdraw_id, $action, $admin_notes, $approved_amount, $_SESSION['admin_username'] ?? '管理员')) {
        $_SESSION['success'] = '提现申请处理成功！';
    } else {
        $_SESSION['error'] = '处理失败，请重试';
    }
    header('Location: withdrawals.php');
    exit;
}

// 手动执行提现
if (isset($_GET['action']) && $_GET['action'] == 'withdraw_now') {
    $results = autoWithdrawByCategory();
    if ($results) {
        $_SESSION['success'] = '提现申请生成成功！请审批';
    } else {
        $_SESSION['info'] = '今日无收入可提现';
    }
    header('Location: withdrawals.php');
    exit;
}

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$per_page = 20;

// 获取数据 - 修复查询问题
$withdrawals = getWithdrawalRecords($page, $per_page, $status);
$today_income = getTodayIncome();
$stats = getWithdrawStats();
$pending_count = getPendingWithdrawCount();

// 调试信息 - 检查数据
error_log("提现记录查询 - 状态: {$status}, 页码: {$page}, 数量: " . count($withdrawals));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>提现管理 - <?php echo SITE_NAME; ?>后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-processed { background-color: #28a745; }
        .badge-failed { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 bg-dark text-white min-vh-100 p-0">
                <?php include 'sidebar.php'; ?>
            </div>
            
            <!-- 主要内容 -->
            <div class="col-md-10">
                <div class="p-4">
                    <h2>提现管理</h2>
                    
                    <!-- 显示消息 -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- 统计卡片 -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_count'] ?? 0; ?></h4>
                                        <small>总申请数</small>
                                    </div>
                                    <i class="fas fa-file-invoice fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $pending_count; ?></h4>
                                        <small>待审批</small>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['processed_count'] ?? 0; ?></h4>
                                        <small>已通过</small>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['failed_count'] ?? 0; ?></h4>
                                        <small>已拒绝</small>
                                    </div>
                                    <i class="fas fa-times-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">操作面板</h5>
                            <div>
                                <a href="withdrawals.php?action=withdraw_now" class="btn btn-primary btn-sm">
                                    <i class="fas fa-money-bill-wave"></i> 生成今日提现申请
                                </a>
                                <a href="withdrawals.php" class="btn btn-outline-secondary btn-sm">全部</a>
                                <a href="withdrawals.php?status=pending" class="btn btn-warning btn-sm">
                                    待审批 <span class="badge bg-dark"><?php echo $pending_count; ?></span>
                                </a>
                                <a href="withdrawals.php?status=processed" class="btn btn-success btn-sm">已通过</a>
                                <a href="withdrawals.php?status=failed" class="btn btn-danger btn-sm">已拒绝</a>
                            </div>
                        </div>
                    </div>

                    <!-- 提现记录表格 -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">提现记录</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($withdrawals)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
<!-- 在提现记录表格中添加费率列 -->
<thead>
    <tr>
        <th>ID</th>
        <th>提现单号</th>
        <th>分类</th>
        <th>原余额</th>
        <th>费率扣除</th>
        <th>申请金额</th>
        <th>审批金额</th>
        <th>订单数</th>
        <th>状态</th>
        <th>创建时间</th>
        <th>处理信息</th>
        <th>操作</th>
    </tr>
</thead>

<tbody>
    <?php foreach ($withdrawals as $withdraw): ?>
    <tr>
        <td><?php echo $withdraw['id']; ?></td>
        <td><small><?php echo $withdraw['withdraw_no']; ?></small></td>
        <td><?php echo htmlspecialchars($withdraw['category_name']); ?></td>
        <td class="fw-bold text-info">
            <?php 
            // 计算原余额（申请金额 + 费率）
            $original_balance = $withdraw['amount'] / (1 - TRANSACTION_FEE_RATE);
            ?>
            ¥<?php echo number_format($original_balance, 2); ?>
        </td>
        <td class="fw-bold text-danger">
            -¥<?php echo number_format($original_balance * TRANSACTION_FEE_RATE, 2); ?>
        </td>
        <td class="fw-bold text-primary">¥<?php echo number_format($withdraw['amount'], 2); ?></td>
        <td class="fw-bold text-success">
            <?php if ($withdraw['approved_amount'] > 0): ?>
            ¥<?php echo number_format($withdraw['approved_amount'], 2); ?>
            <?php else: ?>
            <span class="text-muted">-</span>
            <?php endif; ?>
        </td>
        <td><?php echo $withdraw['order_count']; ?></td>
        <td>
            <span class="badge bg-<?php 
                switch($withdraw['status']) {
                    case 'pending': echo 'warning'; break;
                    case 'processed': echo 'success'; break;
                    case 'failed': echo 'danger'; break;
                }
            ?>">
                <?php 
                switch($withdraw['status']) {
                    case 'pending': echo '待审批'; break;
                    case 'processed': echo '已通过'; break;
                    case 'failed': echo '已拒绝'; break;
                }
                ?>
            </span>
        </td>
        <td><small><?php echo $withdraw['created_at']; ?></small></td>
        <td>
            <?php if ($withdraw['processed_by']): ?>
            <small>处理人: <?php echo $withdraw['processed_by']; ?></small><br>
            <small>时间: <?php echo $withdraw['processed_at']; ?></small>
            <?php if ($withdraw['admin_notes']): ?>
            <br><small>备注: <?php echo htmlspecialchars($withdraw['admin_notes']); ?></small>
            <?php endif; ?>
            <?php else: ?>
            <span class="text-muted">-</span>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($withdraw['status'] === 'pending'): ?>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal"
                        data-id="<?php echo $withdraw['id']; ?>"
                        data-amount="<?php echo $withdraw['amount']; ?>"
                        data-category="<?php echo htmlspecialchars($withdraw['category_name']); ?>">
                    <i class="fas fa-check"></i> 通过
                </button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"
                        data-id="<?php echo $withdraw['id']; ?>"
                        data-amount="<?php echo $withdraw['amount']; ?>"
                        data-category="<?php echo htmlspecialchars($withdraw['category_name']); ?>">
                    <i class="fas fa-times"></i> 拒绝
                </button>
            </div>
            <?php else: ?>
            <span class="text-muted">已处理</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                                </table>
                            </div>

                            <!-- 分页 -->
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $status ? '&status='.$status : ''; ?>">上一页</a>
                                    </li>
                                    <?php endif; ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">第 <?php echo $page; ?> 页</span>
                                    </li>
                                    <?php if (count($withdrawals) == $per_page): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $status ? '&status='.$status : ''; ?>">下一页</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-history fa-3x mb-3"></i>
                                <p>暂无提现记录</p>
                                <?php if ($status): ?>
                                <p class="small">当前筛选状态: 
                                    <?php 
                                    switch($status) {
                                        case 'pending': echo '待审批'; break;
                                        case 'processed': echo '已通过'; break;
                                        case 'failed': echo '已拒绝'; break;
                                        default: echo '全部';
                                    }
                                    ?>
                                </p>
                                <?php endif; ?>
                                <?php if ($pending_count > 0 && $status !== 'pending'): ?>
                                <p class="small">
                                    <a href="withdrawals.php?status=pending" class="text-primary">点击查看 <?php echo $pending_count; ?> 条待审批记录</a>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 通过审批模态框 -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="withdraw_id" id="approveWithdrawId">
                    <div class="modal-header">
                        <h5 class="modal-title">通过提现申请</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">分类</label>
                            <input type="text" class="form-control" id="approveCategory" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">申请金额</label>
                            <input type="text" class="form-control" id="approveAmount" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">审批金额</label>
                            <input type="number" name="approved_amount" class="form-control" step="0.01" min="0" id="approveApprovedAmount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">审批备注</label>
                            <textarea name="admin_notes" class="form-control" rows="3" placeholder="可填写审批说明..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-success">确认通过</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 拒绝审批模态框 -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="withdraw_id" id="rejectWithdrawId">
                    <div class="modal-header">
                        <h5 class="modal-title">拒绝提现申请</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">分类</label>
                            <input type="text" class="form-control" id="rejectCategory" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">申请金额</label>
                            <input type="text" class="form-control" id="rejectAmount" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">拒绝原因</label>
                            <textarea name="admin_notes" class="form-control" rows="3" placeholder="请填写拒绝原因..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-danger">确认拒绝</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 通过模态框数据填充
        var approveModal = document.getElementById('approveModal');
        approveModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('approveWithdrawId').value = button.getAttribute('data-id');
            document.getElementById('approveCategory').value = button.getAttribute('data-category');
            document.getElementById('approveAmount').value = '¥' + button.getAttribute('data-amount');
            document.getElementById('approveApprovedAmount').value = button.getAttribute('data-amount');
        });

        // 拒绝模态框数据填充
        var rejectModal = document.getElementById('rejectModal');
        rejectModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('rejectWithdrawId').value = button.getAttribute('data-id');
            document.getElementById('rejectCategory').value = button.getAttribute('data-category');
            document.getElementById('rejectAmount').value = '¥' + button.getAttribute('data-amount');
        });
    </script>
</body>
</html>