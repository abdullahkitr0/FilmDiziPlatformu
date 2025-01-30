<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Giriş kontrolü
requireLogin();

$page_title = 'Öneriler';
$page_description = 'Size özel film ve dizi önerileri';

try {
    // Kullanıcının izleme listesindeki içerikleri al
    $stmt = $db->prepare("
        SELECT content_id 
        FROM watchlist 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $watchlist_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Kullanıcının en son izlediği/beğendiği içeriklerin kategorilerini al
    $stmt = $db->prepare("
        SELECT DISTINCT cc.category_id
        FROM comments cm
        JOIN contents c ON c.id = cm.content_id
        JOIN content_categories cc ON c.id = cc.content_id
        WHERE cm.user_id = ? AND cm.rating >= 7
        ORDER BY cm.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $favorite_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Önerileri getir
    $recommendations = [];
    
    if (!empty($favorite_categories)) {
        // Kullanıcının beğendiği kategorilerdeki yüksek puanlı içerikleri öner
        $category_placeholders = str_repeat('?,', count($favorite_categories) - 1) . '?';
        $watchlist_exclude = !empty($watchlist_ids) ? 
            "AND c.id NOT IN (" . str_repeat('?,', count($watchlist_ids) - 1) . "?)" : "";
        
        $sql = "
            SELECT c.*, 
                   GROUP_CONCAT(DISTINCT cat.name) as categories,
                   COALESCE(AVG(cm.rating), 0) as avg_rating,
                   COUNT(DISTINCT cm.id) as rating_count
            FROM contents c
            JOIN content_categories cc ON c.id = cc.content_id
            LEFT JOIN categories cat ON cc.category_id = cat.id
            LEFT JOIN comments cm ON c.id = cm.content_id
            WHERE cc.category_id IN ({$category_placeholders})
            {$watchlist_exclude}
            GROUP BY c.id
            HAVING avg_rating >= 7
            ORDER BY c.imdb_rating DESC, avg_rating DESC
            LIMIT 12
        ";
        
        $params = array_merge($favorite_categories, $watchlist_ids);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $recommendations['favorites'] = $stmt->fetchAll();
    }
    
    // En popüler içerikleri öner
    $watchlist_exclude = !empty($watchlist_ids) ? 
        "AND c.id NOT IN (" . str_repeat('?,', count($watchlist_ids) - 1) . "?)" : "";
    
    $sql = "
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT cat.name) as categories,
               COALESCE(AVG(cm.rating), 0) as avg_rating,
               COUNT(DISTINCT cm.id) as rating_count
        FROM contents c
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        LEFT JOIN comments cm ON c.id = cm.content_id
        WHERE c.imdb_rating >= 7
        {$watchlist_exclude}
        GROUP BY c.id
        HAVING avg_rating >= 7
        ORDER BY rating_count DESC, avg_rating DESC
        LIMIT 12
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($watchlist_ids);
    $recommendations['popular'] = $stmt->fetchAll();
    
    // Yeni eklenen yüksek puanlı içerikleri öner
    $sql = "
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT cat.name) as categories,
               COALESCE(AVG(cm.rating), 0) as avg_rating,
               COUNT(DISTINCT cm.id) as rating_count
        FROM contents c
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        LEFT JOIN comments cm ON c.id = cm.content_id
        WHERE c.imdb_rating >= 7
        {$watchlist_exclude}
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT 12
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($watchlist_ids);
    $recommendations['new'] = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('Öneriler yüklenirken bir hata oluştu: ' . $e->getMessage(), 'danger');
    $recommendations = [];
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="h2 mb-4">
        <i class="fas fa-lightbulb me-2"></i>Size Özel Öneriler
    </h1>
    
    <?php if (!empty($recommendations['favorites'])): ?>
        <section class="mb-5">
            <h2 class="h4 mb-4">
                <i class="fas fa-heart me-2"></i>Beğenilerinize Göre Öneriler
            </h2>
            
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($recommendations['favorites'] as $content): ?>
                    <div class="col">
                        <div class="card h-100">
                            <a href="<?= url("content.php?slug=" . $content['slug']) ?>" class="card-img-top-wrapper">
                                <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($content['title']) ?>">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="<?= url("content.php?slug=" . $content['slug']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($content['title']) ?>
                                    </a>
                                </h5>
                                <p class="card-text small text-muted">
                                    <?= $content['categories'] ? htmlspecialchars($content['categories']) : 'Kategorisiz' ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary">
                                        <?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                    </span>
                                    <div>
                                        <?php if ($content['imdb_rating']): ?>
                                        <span class="text-warning me-2" title="IMDb Puanı">
                                            <i class="fas fa-star me-1"></i><?= number_format($content['imdb_rating'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($content['avg_rating'] > 0): ?>
                                        <span class="text-success" title="Kullanıcı Puanı (<?= $content['rating_count'] ?> oy)">
                                            <i class="fas fa-users me-1"></i><?= number_format($content['avg_rating'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i><?= $content['release_year'] ?>
                                    </small>
                                    <form method="POST" action="<?= url('watchlist.php') ?>" class="d-inline">
                                        <?= getCSRFToken() ?>
                                        <input type="hidden" name="content_id" value="<?= $content['id'] ?>">
                                        <button type="submit" name="action" value="add" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Listeye Ekle
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <?php if (!empty($recommendations['popular'])): ?>
        <section class="mb-5">
            <h2 class="h4 mb-4">
                <i class="fas fa-fire me-2"></i>Popüler İçerikler
            </h2>
            
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($recommendations['popular'] as $content): ?>
                    <div class="col">
                        <div class="card h-100">
                            <a href="<?= url("content.php?slug=" . $content['slug']) ?>" class="card-img-top-wrapper">
                                <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($content['title']) ?>">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="<?= url("content.php?slug=" . $content['slug']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($content['title']) ?>
                                    </a>
                                </h5>
                                <p class="card-text small text-muted">
                                    <?= $content['categories'] ? htmlspecialchars($content['categories']) : 'Kategorisiz' ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary">
                                        <?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                    </span>
                                    <div>
                                        <?php if ($content['imdb_rating']): ?>
                                        <span class="text-warning me-2" title="IMDb Puanı">
                                            <i class="fas fa-star me-1"></i><?= number_format($content['imdb_rating'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($content['avg_rating'] > 0): ?>
                                        <span class="text-success" title="Kullanıcı Puanı (<?= $content['rating_count'] ?> oy)">
                                            <i class="fas fa-users me-1"></i><?= number_format($content['avg_rating'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i><?= $content['release_year'] ?>
                                    </small>
                                    <form method="POST" action="<?= url('watchlist.php') ?>" class="d-inline">
                                        <?= getCSRFToken() ?>
                                        <input type="hidden" name="content_id" value="<?= $content['id'] ?>">
                                        <button type="submit" name="action" value="add" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Listeye Ekle
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <?php if (!empty($recommendations['new'])): ?>
        <section class="mb-5">
            <h2 class="h4 mb-4">
                <i class="fas fa-clock me-2"></i>Yeni Eklenen Yüksek Puanlı İçerikler
            </h2>
            
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($recommendations['new'] as $content): ?>
                    <div class="col">
                        <div class="card h-100">
                            <a href="<?= url("content.php?slug=" . $content['slug']) ?>" class="card-img-top-wrapper">
                                <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($content['title']) ?>">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="<?= url("content.php?slug=" . $content['slug']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($content['title']) ?>
                                    </a>
                                </h5>
                                <p class="card-text small text-muted">
                                    <?= $content['categories'] ? htmlspecialchars($content['categories']) : 'Kategorisiz' ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary">
                                        <?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                    </span>
                                    <div>
                                        <?php if ($content['imdb_rating']): ?>
                                        <span class="text-warning me-2" title="IMDb Puanı">
                                            <i class="fas fa-star me-1"></i><?= number_format($content['imdb_rating'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($content['avg_rating'] > 0): ?>
                                        <span class="text-success" title="Kullanıcı Puanı (<?= $content['rating_count'] ?> oy)">
                                            <i class="fas fa-users me-1"></i><?= number_format($content['avg_rating'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i><?= $content['release_year'] ?>
                                    </small>
                                    <form method="POST" action="<?= url('watchlist.php') ?>" class="d-inline">
                                        <?= getCSRFToken() ?>
                                        <input type="hidden" name="content_id" value="<?= $content['id'] ?>">
                                        <button type="submit" name="action" value="add" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Listeye Ekle
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <?php if (empty($recommendations['favorites']) && empty($recommendations['popular']) && empty($recommendations['new'])): ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-lightbulb fa-3x text-muted"></i>
            </div>
            <h2 class="h4 mb-3">Henüz Öneri Yok</h2>
            <p class="text-muted mb-4">
                Size özel öneriler oluşturabilmemiz için daha fazla içeriği değerlendirin ve izleme listenize ekleyin.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= url('latest.php') ?>" class="btn btn-primary">
                    <i class="fas fa-clock me-2"></i>Son Eklenenler
                </a>
                <a href="<?= url('top-rated.php') ?>" class="btn btn-primary">
                    <i class="fas fa-star me-2"></i>En Çok Beğenilenler
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 