<nav class="nav flex-column">
    <div class="p-3 bg-dark text-white">
        <h5 class="mb-0"><?php echo SITE_NAME; ?>后台</h5>
        <small class="text-muted">管理员面板</small>
    </div>
    
    <div class="p-2">
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>仪表盘
        </a>
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
            <i class="fas fa-list me-2"></i>分类管理
        </a>
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
            <i class="fas fa-box me-2"></i>商品管理
        </a>
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'cards.php' ? 'active' : ''; ?>" href="cards.php">
            <i class="fas fa-credit-card me-2"></i>卡密管理
        </a>
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
            <i class="fas fa-shopping-cart me-2"></i>订单管理
        </a>
        <!-- 新增客户统计链接 -->
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'contact_stats.php' ? 'active' : ''; ?>" href="contact_stats.php">
            <i class="fas fa-users me-2"></i>客户统计
        </a>
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'withdrawals.php' ? 'active' : ''; ?>" href="withdrawals.php">
            <i class="fas fa-money-bill-wave me-2"></i>提现管理
        </a>
        <!-- 其他链接保持不变 -->
    </div>
    
    <div class="mt-auto p-2 border-top">
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'change_password.php' ? 'active' : ''; ?>" href="change_password.php">
            <i class="fas fa-key me-2"></i>修改密码
        </a>
        <a class="nav-link text-white" href="logout.php" onclick="return confirm('确定要退出登录吗？')">
            <i class="fas fa-sign-out-alt me-2"></i>退出登录
        </a>
    </div>
</nav>