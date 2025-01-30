<?php
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

// Giriş yapmamış kullanıcıları yönlendir
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Kullanıcı bilgilerini getir
$user = getUserById($user_id);

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz form gönderimi.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        // E-posta güncelleme
        if ($email !== $user['email']) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Geçerli bir e-posta adresi girin.';
            } else {
                // E-posta kullanımda mı kontrol et
                $stmt = $db->prepare("SELECT 1 FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetchColumn()) {
                    $error = 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.';
                } else {
                    $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $stmt->execute([$email, $user_id]);
                    $success = 'E-posta adresiniz güncellendi.';
                }
            }
        }

        // Şifre güncelleme
        if (!empty($current_password) && !empty($new_password)) {
            if (!verifyPassword($current_password, $user['password'])) {
                $error = 'Mevcut şifreniz hatalı.';
            } elseif ($new_password !== $new_password_confirm) {
                $error = 'Yeni şifreler eşleşmiyor.';
            } elseif (strlen($new_password) < 6) {
                $error = 'Yeni şifre en az 6 karakter olmalıdır.';
            } else {
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([hashPassword($new_password), $user_id]);
                $success = 'Şifreniz başarıyla güncellendi.';
            }
        }
    }
    
    // Kullanıcı bilgilerini yeniden yükle
    $user = getUserById($user_id);
}

// Kullanıcının son aktivitelerini getir
$stmt = $db->prepare("
    SELECT c.*, cm.rating, cm.created_at as comment_date
    FROM comments cm
    JOIN contents c ON cm.content_id = c.id
    WHERE cm.user_id = ?
    ORDER BY cm.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_comments = $stmt->fetchAll();

// İzleme listesindeki son içerikleri getir
$stmt = $db->prepare("
    SELECT c.*, w.created_at as added_date
    FROM watchlist w
    JOIN contents c ON w.content_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$watchlist_items = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="container-xl py-4">
    <div class="row">
        <!-- Profil Bilgileri -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Profil Bilgileri</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" 
                                   readonly disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= htmlspecialchars($user['email']) ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h4>Şifre Değiştir</h4>
                        
                        <div class="mb-3">
                            <label class="form-label">Mevcut Şifre</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" name="new_password" class="form-control"
                                   minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre Tekrar</label>
                            <input type="password" name="new_password_confirm" class="form-control"
                                   minlength="6">
                        </div>
                        
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Değişiklikleri Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Aktiviteler -->
        <div class="col-lg-8">
            <!-- Son Yorumlar -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Son Yorumlarım</h3>
                        <a href="comments.php" class="btn btn-link">Tümünü Gör</a>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_comments as $comment): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-1">
                                    <a href="content.php?id=<?= $comment['id'] ?>" class="text-reset">
                                        <?= htmlspecialchars($comment['title']) ?>
                                    </a>
                                </h4>
                                <small class="text-muted">
                                    <?= formatDate($comment['comment_date']) ?>
                                </small>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-blue">Puan: <?= $comment['rating'] ?>/10</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recent_comments)): ?>
                        <div class="list-group-item text-center text-muted">
                            Henüz yorum yapmadınız.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- İzleme Listesi -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">İzleme Listem</h3>
                        <a href="watchlist.php" class="btn btn-link">Tümünü Gör</a>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($watchlist_items as $item): ?>
                        <div class="list-group-item">
                            <div class="row g-3">
                                <div class="col-auto">
                                    <img src="<?= htmlspecialchars($item['poster_url']) ?>" 
                                         class="rounded" style="width: 48px; height: 72px; object-fit: cover;"
                                         alt="<?= htmlspecialchars($item['title']) ?>">
                                </div>
                                <div class="col">
                                    <h4 class="mb-1">
                                        <a href="content.php?id=<?= $item['id'] ?>" class="text-reset">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </a>
                                    </h4>
                                    <div class="mt-1">
                                        <span class="badge bg-blue">
                                            <?= $item['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                        </span>
                                        <span class="badge bg-yellow">
                                            IMDB: <?= number_format($item['imdb_rating'], 1) ?>
                                        </span>
                                        <small class="text-muted ms-2">
                                            Eklenme: <?= formatDate($item['added_date']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($watchlist_items)): ?>
                        <div class="list-group-item text-center text-muted">
                            İzleme listenizde henüz içerik yok.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 