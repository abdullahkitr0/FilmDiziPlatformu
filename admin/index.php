<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// İstatistikleri getir
$stats = [
    'total_contents' => $db->query("SELECT COUNT(*) FROM contents")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_comments' => $db->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'total_categories' => $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'latest_contents' => $db->query("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT cat.name) as category_name
        FROM contents c
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        GROUP BY c.id
        ORDER BY c.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC),
    'latest_users' => $db->query("
        SELECT * FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC),
    'latest_comments' => $db->query("
        SELECT c.*, u.username, cnt.title as content_title
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN contents cnt ON c.content_id = cnt.id
        ORDER BY c.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC)
];

$page_title = 'Gösterge Paneli';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin Paneli</title>
    <!-- Tabler.io CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
</head>
<body>
    <div class="page">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="page-wrapper">
            <!-- Sayfa başlığı -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                <?= $page_title ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <!-- İstatistik kartları -->
                    <div class="row row-deck row-cards mb-4">
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Toplam İçerik</div>
                                    </div>
                                    <div class="h1 mb-3"><?= $stats['total_contents'] ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Toplam Kullanıcı</div>
                                    </div>
                                    <div class="h1 mb-3"><?= $stats['total_users'] ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Toplam Yorum</div>
                                    </div>
                                    <div class="h1 mb-3"><?= $stats['total_comments'] ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Toplam Kategori</div>
                                    </div>
                                    <div class="h1 mb-3"><?= $stats['total_categories'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Son eklenen içerikler -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Son Eklenen İçerikler</h3>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($stats['latest_contents'] as $content): ?>
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                                                         class="avatar avatar-md">
                                                </div>
                                                <div class="col">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div class="text-truncate">
                                                                <?= htmlspecialchars($content['title']) ?>
                                                            </div>
                                                            <small class="text-muted">
                                                                <?= $content['category_name'] ? htmlspecialchars($content['category_name']) : 'Kategorisiz' ?>
                                                            </small>
                                                        </div>
                                                        <div>
                                                            <a href="<?= url("admin/content-form.php?id={$content['id']}") ?>" class="btn btn-sm btn-primary">
                                                                Düzenle
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Son yorumlar -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Son Yorumlar</h3>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($stats['latest_comments'] as $comment): ?>
                                        <div class="list-group-item">
                                            <div class="row">
                                                <div class="col">
                                                    <div class="text-truncate">
                                                        <?= htmlspecialchars($comment['comment']) ?>
                                                    </div>
                                                    <div class="text-muted mt-1">
                                                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                                        tarafından
                                                        <strong><?= htmlspecialchars($comment['content_title']) ?></strong>
                                                        için
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="<?= url("admin/comments.php?action=edit&id={$comment['id']}") ?>" class="btn btn-sm btn-primary">
                                                        Düzenle
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require_once 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html> 