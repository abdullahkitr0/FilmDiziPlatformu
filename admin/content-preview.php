<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// İçerik ID'si kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('admin/contents.php');
}

// İçeriği getir
$stmt = $db->prepare("
    SELECT c.*, 
           GROUP_CONCAT(cat.name) as category_names,
           GROUP_CONCAT(cat.id) as category_ids
    FROM contents c
    LEFT JOIN content_categories cc ON c.id = cc.content_id
    LEFT JOIN categories cat ON cc.category_id = cat.id
    WHERE c.id = ?
    GROUP BY c.id
");

$stmt->execute([$_GET['id']]);
$content = $stmt->fetch();

if (!$content) {
    redirect('admin/contents.php');
}

// Kategori isimlerini diziye çevir
$category_names = $content['category_names'] ? explode(',', $content['category_names']) : [];

// Platform bilgilerini getir
$stmt = $db->prepare("
    SELECT p.*
    FROM platforms p
    JOIN content_platform_relations cpr ON p.id = cpr.platform_id
    WHERE cpr.content_id = ?
");

$stmt->execute([$_GET['id']]);
$platforms = $stmt->fetchAll();

$page_title = 'İçerik Önizleme: ' . $content['title'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin Paneli</title>
    <!-- Tabler.io CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <div class="col-auto ms-auto">
                            <div class="btn-list">
                                <a href="content-form.php?id=<?= $content['id'] ?>" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4" />
                                        <line x1="13.5" y1="6.5" x2="17.5" y2="10.5" />
                                    </svg>
                                    Düzenle
                                </a>
                                <a href="../content.php?id=<?= $content['id'] ?>" class="btn btn-primary" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                    Site Önizleme
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row">
                        <!-- İçerik detayları -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="<?= htmlspecialchars($content['title']) ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <h2><?= htmlspecialchars($content['title']) ?></h2>
                                            <div class="mt-3">
                                                <span class="badge bg-blue">
                                                    <?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                                </span>
                                                <?php foreach ($category_names as $category_name): ?>
                                                <span class="badge bg-green">
                                                    <?= htmlspecialchars($category_name) ?>
                                                </span>
                                                <?php endforeach; ?>
                                                <span class="badge bg-yellow">
                                                    IMDB: <?= number_format($content['imdb_rating'], 1) ?>
                                                </span>
                                            </div>
                                            <div class="mt-3">
                                                <strong>Yayın Tarihi:</strong>
                                                <?= formatDate($content['release_date']) ?>
                                            </div>
                                            <?php if ($platforms): ?>
                                            <div class="mt-3">
                                                <strong>İzleme Platformları:</strong>
                                                <div class="mt-2">
                                                    <?php foreach ($platforms as $platform): ?>
                                                    <a href="<?= htmlspecialchars($platform['website']) ?>" 
                                                       class="btn btn-outline-primary btn-sm me-2 mb-2"
                                                       target="_blank">
                                                        <img src="<?= htmlspecialchars($platform['logo_url']) ?>" 
                                                             alt="<?= htmlspecialchars($platform['name']) ?>"
                                                             height="20">
                                                        <?= htmlspecialchars($platform['name']) ?>
                                                    </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($content['trailer_url']): ?>
                                            <div class="mt-3">
                                                <a href="<?= htmlspecialchars($content['trailer_url']) ?>" 
                                                   class="btn btn-danger"
                                                   target="_blank">
                                                    <i class="fab fa-youtube me-2"></i>
                                                    Fragmanı İzle
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <h3>Açıklama</h3>
                                        <div class="text-muted">
                                            <?= nl2br(htmlspecialchars($content['description'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO ve meta bilgileri -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">SEO Bilgileri</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Meta Başlık</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($content['meta_title']) ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Meta Açıklama</label>
                                        <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($content['meta_description']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Meta Anahtar Kelimeler</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($content['meta_keywords']) ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SEO URL</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($content['seo_url']) ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">Sistem Bilgileri</h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Oluşturulma Tarihi</label>
                                        <input type="text" class="form-control" value="<?= formatDate($content['created_at']) ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Son Güncelleme</label>
                                        <input type="text" class="form-control" value="<?= formatDate($content['updated_at']) ?>" readonly>
                                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html> 