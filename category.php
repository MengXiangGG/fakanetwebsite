<?php
require_once 'includes/functions.php';

// 支持slug参数
if (isset($_GET['slug'])) {
    $slug = safe_input($_GET['slug']);
    $category = getCategoryBySlug($slug);
} else {
    // 兼容旧的id方式
    $category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $category = getCategoryById($category_id);
}

if (!$category) {
    header('Location: index.php');
    exit;
}

// 获取分类下的商品（包含库存信息）
$products = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND status = 0) as stock
    FROM products p 
    WHERE p.category_id = ? AND p.status = 1 
    ORDER BY p.id DESC
");
$products->execute([$category['id']]);
$products = $products->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .product-card {
            border-left: 4px solid #3498db;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        .navbar-brand {
            cursor: default; /* 移除手型光标 */
        }
        .navbar-brand:hover {
            color: inherit !important; /* 移除悬停颜色变化 */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!-- 修改：移除链接，只显示文字 -->
            <span class="navbar-brand">
                <i class="fas fa-shopping-cart me-2"></i><?php echo SITE_NAME; ?>
            </span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <!-- 删除面包屑导航 -->
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                </div>
                
                <p class="text-muted mb-4"><?php echo htmlspecialchars($category['description']); ?></p>
            </div>
        </div>

        <div class="row">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card position-relative h-100">
                        <!-- 库存徽章 -->
                        <span class="stock-badge badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                            <?php echo $product['stock'] > 0 ? '有货' : '缺货'; ?>
                        </span>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price-tag">¥<?php echo $product['price']; ?></span>
                                    <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $product['stock'] > 0 ? '有货' : '缺货'; ?>
                                    </span>
                                </div>
                                <!-- 库存信息 -->
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-box me-1"></i>
                                        库存: <?php echo $product['stock']; ?> 件
                                    </small>
                                </div>
                                <div class="mt-2">
                                    <?php if ($product['stock'] > 0): ?>
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary w-100">
                                        立即购买
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        暂时缺货
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>该分类下暂无商品
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 分享模态框 -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">分享分类</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>复制以下链接分享给他人：</p>
                    <div class="share-link" id="shareUrl"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                    <button type="button" class="btn btn-primary" onclick="copyShareUrl()">复制链接</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.share-btn').addEventListener('click', function() {
            const slug = this.getAttribute('data-slug');
            const shareUrl = `${window.location.origin}<?php echo SITE_URL; ?>category.php?slug=${slug}`;
            document.getElementById('shareUrl').textContent = shareUrl;
            new bootstrap.Modal(document.getElementById('shareModal')).show();
        });

        function copyShareUrl() {
            const shareUrl = document.getElementById('shareUrl').textContent;
            navigator.clipboard.writeText(shareUrl).then(() => {
                alert('链接已复制到剪贴板！');
            });
        }

        // 阻止左上角品牌链接的默认行为（额外保护）
        document.querySelector('.navbar-brand').addEventListener('click', function(e) {
            e.preventDefault();
        });
    </script>
</body>
</html>