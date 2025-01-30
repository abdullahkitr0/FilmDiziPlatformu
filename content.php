<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// İçerik slug'ını al
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    redirect();
}

try {
    // İçeriği getir
    $stmt = $db->prepare("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT cat.name) as categories,
               GROUP_CONCAT(DISTINCT cat.slug) as category_slugs,
               COALESCE(AVG(cm.rating), 0) as avg_rating,
               COUNT(DISTINCT cm.id) as rating_count
        FROM contents c
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        LEFT JOIN comments cm ON c.id = cm.content_id
        WHERE c.slug = ?
        GROUP BY c.id
    ");
    $stmt->execute([$slug]);
    $content = $stmt->fetch();
    
    if (!$content) {
        setFlashMessage('İçerik bulunamadı.', 'danger');
        redirect();
    }
    
    // Görüntülenme sayısını artır
    logContentView($content['id']);
    
    $page_title = $content['title'];
    $page_description = $content['meta_description'] ?? truncate(strip_tags($content['description']), 160);
    
    // Kategorileri dizi haline getir
    $categories = [];
    if ($content['categories'] && $content['category_slugs']) {
        $names = explode(',', $content['categories']);
        $slugs = explode(',', $content['category_slugs']);
        foreach ($names as $i => $name) {
            $categories[] = [
                'name' => $name,
                'slug' => $slugs[$i]
            ];
        }
    }
    
    // Kullanıcının puanını getir
    $user_rating = 0;
    if (isLoggedIn()) {
        $stmt = $db->prepare("
            SELECT rating 
            FROM comments 
            WHERE user_id = ? AND content_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id'], $content['id']]);
        $user_rating = $stmt->fetchColumn();
    }
    
    // Yorumları getir
    $stmt = $db->prepare("
        SELECT c.*, 
               u.username,
               u.avatar_url
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.content_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$content['id']]);
    $comments = $stmt->fetchAll();
    
    // Benzer içerikleri getir
    $stmt = $db->prepare("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT cat.name) as categories,
               COALESCE(AVG(cm.rating), 0) as avg_rating,
               COUNT(DISTINCT cm.id) as rating_count
        FROM contents c
        JOIN content_categories cc1 ON c.id = cc1.content_id
        JOIN content_categories cc2 ON cc1.category_id = cc2.category_id
        LEFT JOIN content_categories cc3 ON c.id = cc3.content_id
        LEFT JOIN categories cat ON cc3.category_id = cat.id
        LEFT JOIN comments cm ON c.id = cm.content_id
        WHERE cc2.content_id = ?
        AND c.id != ?
        GROUP BY c.id
        ORDER BY c.imdb_rating DESC
        LIMIT 6
    ");
    $stmt->execute([$content['id'], $content['id']]);
    $similar_contents = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('Veriler yüklenirken bir hata oluştu: ' . $e->getMessage(), 'danger');
    redirect();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $contentId = $_POST['content_id']; // İçerik ID'si
    if ($_POST['action'] === 'add') {
        // İzleme listesine ekleme işlemi
        addToWatchlist($_SESSION['user_id'], $contentId);
        setFlash('success', 'İçerik izleme listesine eklendi.');
    } elseif ($_POST['action'] === 'remove') {
        // İzleme listesinden çıkarma işlemi
        removeFromWatchlist($_SESSION['user_id'], $contentId);
        setFlash('success', 'İçerik izleme listesinden çıkarıldı.');
    }
    
    // Yönlendirme
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sol Kolon: Poster ve Temel Bilgiler -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($content['title']) ?>">
                
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary">
                            <?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                        </span>
                        <div>
                            <?php if ($content['imdb_rating']): ?>
                            <span class="text-warning" title="IMDb Puanı">
                                <i class="fas fa-star me-1"></i><?= number_format($content['imdb_rating'], 1) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (!isInWatchlist($content['id'])): ?>
                            <form method="POST" action="<?= url('comments.php') ?>" class="mb-3">
                                <?= getCSRFToken() ?>
                                <input type="hidden" name="content_id" value="<?= $content['id'] ?>">
                                <button type="submit" name="action" value="add" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>İzleme Listeme Ekle
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="<?= url('comments.php') ?>" class="mb-3">
                                <?= getCSRFToken() ?>
                                <input type="hidden" name="content_id" value="<?= $content['id'] ?>">
                                <button type="submit" name="action" value="remove" class="btn btn-danger w-100">
                                    <i class="fas fa-minus me-2"></i>Listemden Çıkar
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="list-group list-group-flush">
                        <?php if ($content['release_year']): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Yıl</span>
                                <span><?= $content['release_year'] ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($content['runtime']): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Süre</span>
                                <span><?= $content['runtime'] ?> dk</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($content['language']): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Dil</span>
                                <span><?= htmlspecialchars($content['language']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($content['country']): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Ülke</span>
                                <span><?= htmlspecialchars($content['country']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($content['director']): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Yönetmen</span>
                                <span><?= htmlspecialchars($content['director']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($content['writer']): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Senarist</span>
                                <span><?= htmlspecialchars($content['writer']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sağ Kolon: Detaylar -->
        <div class="col-md-8">
            <h1 class="h2 mb-2"><?= htmlspecialchars($content['title']) ?></h1>
            
            <?php if ($content['original_title'] && $content['original_title'] !== $content['title']): ?>
                <h2 class="h5 text-muted mb-3"><?= htmlspecialchars($content['original_title']) ?></h2>
            <?php endif; ?>
            
            <?php if (!empty($categories)): ?>
                <div class="mb-3">
                    <?php foreach ($categories as $category): ?>
                        <a href="<?= url("category.php?slug=" . $category['slug']) ?>" class="badge bg-secondary text-decoration-none me-1">
                            <?= htmlspecialchars($category['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($content['description']): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Özet</h3>
                        <p class="card-text"><?= nl2br(htmlspecialchars($content['description'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($content['cast']): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Oyuncular</h3>
                        <p class="card-text"><?= htmlspecialchars($content['cast']) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($content['awards']): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Ödüller</h3>
                        <p class="card-text"><?= htmlspecialchars($content['awards']) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($content['trailer_url']): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Fragman</h3>
                        <div class="ratio ratio-16x9">
                            <iframe src="<?= htmlspecialchars($content['trailer_url']) ?>" 
                                    allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Yorumlar -->
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="h5 mb-4">
                        Yorumlar
                        <?php if ($content['rating_count'] > 0): ?>
                            <small class="text-muted">
                                (<?= $content['rating_count'] ?> yorum, <?= number_format($content['avg_rating'], 1) ?>/10)
                            </small>
                        <?php endif; ?>
                    </h3>
                    
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="<?= url('comments.php') ?>" class="mb-4">
                            <?= getCSRFToken() ?>
                            <input type="hidden" name="content_id" value="<?= $content['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Puanınız</label>
                                <select name="rating" class="form-select" required>
                                    <?php for ($i = 10; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>" <?= $user_rating == $i ? 'selected' : '' ?>>
                                            <?= $i ?>/10
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Yorumunuz</label>
                                <textarea name="comment" class="form-control" rows="3" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Yorum Yap
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-4">
                            Yorum yapabilmek için <a href="<?= url('login.php') ?>" class="alert-link">giriş yapmalısınız</a>.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($comments)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($comments as $comment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="<?= $comment['avatar_url'] ?: url('assets/img/default-avatar.png') ?>" 
                                             class="rounded-circle me-2" 
                                             width="32" 
                                             height="32" 
                                             alt="<?= htmlspecialchars($comment['username']) ?>">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($comment['username']) ?></h6>
                                            <small class="text-muted">
                                                <?= formatDate($comment['created_at']) ?>
                                                <?php if ($comment['rating']): ?>
                                                    • <span class="text-warning">
                                                        <i class="fas fa-star"></i> <?= $comment['rating'] ?>/10
                                                    </span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Benzer İçerikler -->
            <?php if (!empty($similar_contents)): ?>
                <h3 class="h5 mb-3">Benzer İçerikler</h3>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                    <?php foreach ($similar_contents as $similar): ?>
                        <div class="col">
                            <div class="card h-100">
                                <a href="<?= url("content.php?slug=" . $similar['slug']) ?>" class="card-img-top-wrapper">
                                    <img src="<?= htmlspecialchars($similar['poster_url']) ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($similar['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?= url("content.php?slug=" . $similar['slug']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($similar['title']) ?>
                                        </a>
                                    </h5>
                                    <p class="card-text small text-muted">
                                        <?= $similar['categories'] ? htmlspecialchars($similar['categories']) : 'Kategorisiz' ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">
                                            <?= $similar['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                        </span>
                                        <div>
                                            <?php if ($similar['imdb_rating']): ?>
                                            <span class="text-warning me-2" title="IMDb Puanı">
                                                <i class="fas fa-star me-1"></i><?= number_format($similar['imdb_rating'], 1) ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if ($similar['avg_rating'] > 0): ?>
                                            <span class="text-success" title="Kullanıcı Puanı (<?= $similar['rating_count'] ?> oy)">
                                                <i class="fas fa-users me-1"></i><?= number_format($similar['avg_rating'], 1) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 