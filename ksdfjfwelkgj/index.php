<?php
session_start();

// 检查是否已登录
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // 已登录，跳转到仪表盘
    header('Location: dashboard.php');
    exit;
} else {
    // 未登录，跳转到登录页面
    header('Location: login.php');
    exit;
}
?>