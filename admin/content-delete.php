<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// ID kontrolü
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    setFlashMessage('Geçersiz içerik ID\'si.', 'danger');
    header('Location: contents.php');
    exit;
}

// İçeriği getir
$stmt = $db->prepare("SELECT * FROM contents WHERE id = ?");
$stmt->execute([$id]);
$content = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$content) {
    setFlashMessage('İçerik bulunamadı.', 'danger');
    header('Location: contents.php');
    exit;
}

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Güvenlik doğrulaması başarısız.', 'danger');
        header('Location: contents.php');
        exit;
    }

    try {
        $db->beginTransaction();

        // İlişkili kayıtları sil
        $tables = [
            'comments' => 'content_id',
            'watchlist' => 'content_id',
            'content_platforms' => 'content_id',
            'content_views' => 'content_id',
            'user_activities' => 'content_id',
            'content_categories' => 'content_id'
        ];

        foreach ($tables as $table => $column) {
            $stmt = $db->prepare("DELETE FROM $table WHERE $column = ?");
            $stmt->execute([$id]);
        }

        // Poster resmini sil
        if (!empty($content['poster_url'])) {
            $poster_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($content['poster_url'], PHP_URL_PATH);
            if (file_exists($poster_path)) {
                unlink($poster_path);
            }
        }

        // İçeriği sil
        $stmt = $db->prepare("DELETE FROM contents WHERE id = ?");
        $stmt->execute([$id]);

        // Admin log
        logAdminActivity('content_delete', [
            'content_id' => $id,
            'content_title' => $content['title'],
            'content_type' => $content['type']
        ]);

        $db->commit();
        setFlashMessage('İçerik başarıyla silindi.', 'success');
        header('Location: contents.php');
        exit;

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("İçerik silme hatası: " . $e->getMessage());
        setFlashMessage('İçerik silinirken bir hata oluştu.', 'danger');
        header('Location: contents.php');
        exit;
    }
}

// Onay sayfasını göster
$page_title = "İçerik Silme";
require_once 'includes/header.php';
?>

<div class="container-xl py-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">İçerik Silme Onayı</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <h4 class="alert-title">Dikkat!</h4>
                <p>
                    "<strong><?= htmlspecialchars($content['title']) ?></strong>" başlıklı içeriği silmek üzeresiniz.
                    Bu işlem geri alınamaz ve aşağıdaki veriler de silinecektir:
                </p>
                <ul class="mb-0">
                    <li>İçeriğe ait tüm yorumlar</li>
                    <li>Kullanıcıların izleme listelerindeki kayıtlar</li>
                    <li>İzlenme istatistikleri</li>
                    <li>Platform bağlantıları</li>
                    <li>İçerikle ilgili tüm aktivite kayıtları</li>
                </ul>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">İçerik Bilgileri</h4>
                            <dl class="row">
                                <dt class="col-5">Başlık:</dt>
                                <dd class="col-7"><?= htmlspecialchars($content['title']) ?></dd>
                                
                                <dt class="col-5">Tür:</dt>
                                <dd class="col-7"><?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?></dd>
                                
                                <dt class="col-5">IMDB Puanı:</dt>
                                <dd class="col-7"><?= number_format($content['imdb_rating'], 1) ?></dd>
                                
                                <dt class="col-5">Yayın Yılı:</dt>
                                <dd class="col-7"><?= $content['release_year'] ?></dd>
                                
                                <dt class="col-5">Eklenme Tarihi:</dt>
                                <dd class="col-7"><?= formatDate($content['created_at']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <form method="post" class="card">
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-3">
                                <div class="form-label">Onay</div>
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input" required>
                                    <span class="form-check-label">
                                        Bu içeriği silmek istediğimi onaylıyorum
                                    </span>
                                </label>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="contents.php" class="btn btn-link">İptal</a>
                                <button type="submit" class="btn btn-danger">
                                    İçeriği Sil
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 