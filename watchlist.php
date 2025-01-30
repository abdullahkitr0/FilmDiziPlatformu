<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

// Giriş kontrolü
requireLogin();

$page_title = 'İzleme Listem';

// İzleme listesi işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken()) {
        setFlashMessage('Geçersiz form gönderimi.', 'danger');
        redirect('watchlist.php');
    }

    $content_id = filter_var($_POST['content_id'] ?? 0, FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    
    if ($content_id) {
        if ($action === 'add') {
            // İzleme listesine ekle
            try {
                $stmt = $db->prepare("INSERT INTO watchlist (user_id, content_id, created_at) VALUES (?, ?, NOW())");
                if ($stmt->execute([$_SESSION['user_id'], $content_id])) {
                    setFlashMessage('İçerik izleme listenize eklendi.', 'success');
                    logUserActivity('watchlist_add', ['content_id' => $content_id]);
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    setFlashMessage('Bu içerik zaten izleme listenizde.', 'warning');
                } else {
                    setFlashMessage('Bir hata oluştu: ' . $e->getMessage(), 'danger');
                }
            }
        } elseif ($action === 'remove') {
            // İzleme listesinden çıkar
            try {
                $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND content_id = ?");
                if ($stmt->execute([$_SESSION['user_id'], $content_id])) {
                    setFlashMessage('İçerik izleme listenizden çıkarıldı.', 'success');
                    logUserActivity('watchlist_remove', ['content_id' => $content_id]);
                }
            } catch (PDOException $e) {
                setFlashMessage('Bir hata oluştu: ' . $e->getMessage(), 'danger');
            }
        }
    }
    
    // Eğer bir içerik sayfasından geldiyse, o sayfaya geri dön
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'content.php') !== false) {
        redirect($_SERVER['HTTP_REFERER']);
    }
}

// İzleme listesini getir
try {
    $stmt = $db->prepare("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT cat.name) as categories,
               w.created_at as added_at,
               COALESCE(AVG(cm.rating), 0) as avg_rating,
               COUNT(DISTINCT cm.id) as rating_count
        FROM watchlist w
        JOIN contents c ON w.content_id = c.id
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        LEFT JOIN comments cm ON c.id = cm.content_id
        WHERE w.user_id = ?
        GROUP BY c.id, w.created_at
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $watchlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setFlashMessage('İzleme listesi yüklenirken bir hata oluştu: ' . $e->getMessage(), 'danger');
    $watchlist = [];
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">
            <i class="fas fa-list me-2"></i>İzleme Listem
        </h1>
        <a href="<?= url('search.php') ?>" class="btn btn-primary">
            <i class="fas fa-search me-2"></i>Yeni İçerik Ekle
        </a>
    </div>
    
    <?php if (!empty($watchlist)): ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($watchlist as $content): ?>
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
                                <small class="text-muted" title="Listeye Eklenme Tarihi">
                                    <i class="fas fa-clock me-1"></i><?= formatDate($content['added_at']) ?>
                                </small>
                                <form method="POST" action="" class="d-inline">
                                    <?= getCSRFToken() ?>
                                    <input type="hidden" name="content_id" value="<?= $content['id'] ?>">
                                    <button type="submit" 
                                            name="action" 
                                            value="remove" 
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Bu içeriği izleme listenizden çıkarmak istediğinize emin misiniz?')">
                                        <i class="fas fa-trash me-1"></i>Çıkar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-list fa-3x text-muted"></i>
            </div>
            <h2 class="h4 mb-3">İzleme Listeniz Boş</h2>
            <p class="text-muted mb-4">
                Henüz izleme listenize hiç film veya dizi eklememişsiniz.
            </p>
            <a href="<?= url() ?>" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Film ve Dizileri Keşfet
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 