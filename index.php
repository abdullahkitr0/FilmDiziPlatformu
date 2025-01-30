<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Ana Sayfa';
$page_description = SITE_DESCRIPTION;

// Son eklenen içerikleri getir
try {
    $stmt = $db->query("
        SELECT c.*, GROUP_CONCAT(cat.name) as categories
        FROM contents c
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT 12
    ");
    $latest_contents = $stmt->fetchAll();
} catch (PDOException $e) {
    $latest_contents = [];
    setFlashMessage('İçerikler yüklenirken bir hata oluştu.', 'danger');
}

// En yüksek puanlı içerikleri getir
try {
    $stmt = $db->query("
        SELECT c.*, GROUP_CONCAT(cat.name) as categories,
               COALESCE(AVG(cm.rating), 0) as avg_rating,
               COUNT(DISTINCT cm.id) as rating_count
        FROM contents c
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        LEFT JOIN comments cm ON c.id = cm.content_id
        GROUP BY c.id
        HAVING avg_rating > 0
        ORDER BY avg_rating DESC, rating_count DESC
        LIMIT 12
    ");
    $top_rated = $stmt->fetchAll();
} catch (PDOException $e) {
    $top_rated = [];
    setFlashMessage('İçerikler yüklenirken bir hata oluştu.', 'danger');
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Hero Section -->
    <div class="hero rounded-3 p-5 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 mb-3">Film ve Dizi Dünyasına Hoş Geldiniz!</h1>
                <p class="lead mb-4">
                    En yeni filmler, popüler diziler ve kullanıcı değerlendirmeleri burada.
                    Hemen keşfetmeye başlayın!
                </p>
                <div class="d-flex gap-3">
                    <a href="<?= url('search.php') ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-2"></i>İçerikleri Keşfet
                    </a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="<?= url('register.php') ?>" class="btn btn-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Üye Ol
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Eklenenler -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">
                <i class="fas fa-clock me-2"></i>Son Eklenenler
            </h2>
            <a href="<?= url('latest.php') ?>" class="btn btn-primary btn-sm">
                Tümünü Gör
            </a>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($latest_contents as $content): ?>
            <div class="col">
                <div class="card h-100">
                    <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                         class="card-img-top" alt="<?= htmlspecialchars($content['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?= url("content.php?slug={$content['slug']}") ?>" class="text-decoration-none">
                                <?= htmlspecialchars($content['title']) ?>
                            </a>
                        </h5>
                        <p class="card-text small text-muted">
                            <?= $content['categories'] ? htmlspecialchars($content['categories']) : 'Kategorisiz' ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary">
                                <?= ucfirst($content['type']) ?>
                            </span>
                            <?php if ($content['imdb_rating']): ?>
                            <span class="text-warning">
                                <i class="fas fa-star me-1"></i><?= number_format($content['imdb_rating'], 1) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- En Çok Beğenilenler -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">
                <i class="fas fa-star me-2"></i>En Çok Beğenilenler
            </h2>
            <a href="<?= url('top-rated.php') ?>" class="btn btn-primary btn-sm">
                Tümünü Gör
            </a>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($top_rated as $content): ?>
            <div class="col">
                <div class="card h-100">
                    <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                         class="card-img-top" alt="<?= htmlspecialchars($content['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?= url("content.php?slug={$content['slug']}") ?>" class="text-decoration-none">
                                <?= htmlspecialchars($content['title']) ?>
                            </a>
                        </h5>
                        <p class="card-text small text-muted">
                            <?= $content['categories'] ? htmlspecialchars($content['categories']) : 'Kategorisiz' ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary">
                                <?= ucfirst($content['type']) ?>
                            </span>
                            <div>
                                <?php if ($content['imdb_rating']): ?>
                                <span class="text-warning me-2" title="IMDb Puanı">
                                    <i class="fas fa-star me-1"></i><?= number_format($content['imdb_rating'], 1) ?>
                                </span>
                                <?php endif; ?>
                                <span class="text-success" title="Kullanıcı Puanı">
                                    <i class="fas fa-users me-1"></i><?= number_format($content['avg_rating'], 1) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?> 