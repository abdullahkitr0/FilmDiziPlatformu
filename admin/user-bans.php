<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Sayfa başlığı
$page_title = 'Kullanıcı Yasakları';

// Yasaklama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban'])) {
    try {
        // CSRF kontrolü
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('CSRF token doğrulaması başarısız.');
        }
        
        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        $reason = trim($_POST['reason']);
        $duration = $_POST['duration'];
        
        if (!$user_id) {
            throw new Exception('Geçersiz kullanıcı ID\'si.');
        }
        
        if (empty($reason)) {
            throw new Exception('Yasaklama nedeni belirtmelisiniz.');
        }
        
        $db->beginTransaction();
        
        // Kullanıcıyı kontrol et
        $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Kullanıcı bulunamadı.');
        }
        
        // Bitiş tarihini hesapla
        $expires_at = null;
        if ($duration !== 'permanent') {
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration}"));
        }
        
        // Yasaklamayı kaydet
        $stmt = $db->prepare("
            INSERT INTO user_bans (
                user_id, banned_by, reason, expires_at,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $_SESSION['user_id'],
            $reason,
            $expires_at
        ]);
        
        // Admin log
        logAdminActivity('user_ban', [
            'user_id' => $user_id,
            'username' => $user['username'],
            'reason' => $reason,
            'duration' => $duration,
            'expires_at' => $expires_at
        ]);
        
        $db->commit();
        setFlashMessage('Kullanıcı başarıyla yasaklandı.', 'success');
    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
    
    redirect('admin/user-bans.php');
    exit;
}

// Yasak kaldırma işlemi
if (isset($_GET['unban'])) {
    try {
        $user_id = filter_var($_GET['unban'], FILTER_VALIDATE_INT);
        if (!$user_id) throw new Exception('Geçersiz kullanıcı ID\'si.');
        
        $db->beginTransaction();
        
        // Kullanıcıyı kontrol et
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $username = $stmt->fetchColumn();
        
        if (!$username) {
            throw new Exception('Kullanıcı bulunamadı.');
        }
        
        // Yasağı kaldır
        $stmt = $db->prepare("
            UPDATE user_bans 
            SET removed_at = NOW(), 
                removed_by = ? 
            WHERE user_id = ? 
            AND removed_at IS NULL
        ");
        
        $stmt->execute([$_SESSION['user_id'], $user_id]);
        
        // Admin log
        logAdminActivity('user_unban', [
            'user_id' => $user_id,
            'username' => $username
        ]);
        
        $db->commit();
        setFlashMessage('Kullanıcı yasağı başarıyla kaldırıldı.', 'success');
    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
    
    redirect('admin/user-bans.php');
    exit;
}

// Yasaklı kullanıcıları getir
try {
    $stmt = $db->prepare("
        SELECT b.*, 
               u.username,
               bu.username as banned_by_username,
               ru.username as removed_by_username,
               CASE 
                   WHEN b.removed_at IS NOT NULL THEN 'removed'
                   WHEN b.expires_at IS NOT NULL AND b.expires_at <= NOW() THEN 'expired'
                   ELSE 'active'
               END as ban_status
        FROM user_bans b
        JOIN users u ON b.user_id = u.id
        JOIN users bu ON b.banned_by = bu.id
        LEFT JOIN users ru ON b.removed_by = ru.id
        ORDER BY 
            CASE 
                WHEN b.removed_at IS NULL AND (b.expires_at IS NULL OR b.expires_at > NOW()) THEN 1
                ELSE 2
            END,
            b.created_at DESC
    ");
    $stmt->execute();
    $bans = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('Yasaklı kullanıcılar yüklenirken bir hata oluştu: ' . $e->getMessage(), 'danger');
    $bans = [];
}

// Kullanıcıları getir (yasaklama formu için)
try {
    $stmt = $db->prepare("
        SELECT id, username, email 
        FROM users 
        WHERE id NOT IN (
            SELECT user_id 
            FROM user_bans 
            WHERE removed_at IS NULL 
            AND (expires_at IS NULL OR expires_at > NOW())
        )
        AND id != ?  -- Admin kendini yasaklayamasın
        ORDER BY username
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('Kullanıcılar yüklenirken bir hata oluştu: ' . $e->getMessage(), 'danger');
    $users = [];
}

// CSRF token oluştur
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
                        <div class="col-auto ms-auto">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#banModal">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9" />
                                    <line x1="5.7" y1="5.7" x2="18.3" y2="18.3" />
                                </svg>
                                Kullanıcı Yasakla
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Yasaklı Kullanıcılar</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Kullanıcı</th>
                                        <th>Neden</th>
                                        <th>Yasaklayan</th>
                                        <th>Tarih</th>
                                        <th>Bitiş</th>
                                        <th>Durum</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bans as $ban): ?>
                                        <tr>
                                            <td>
                                                <a href="users.php?id=<?= $ban['user_id'] ?>">
                                                    <?= htmlspecialchars($ban['username'] ?? 'Silinmiş Kullanıcı') ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($ban['reason']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($ban['banned_by_username'] ?? 'Bilinmiyor') ?>
                                            </td>
                                            <td>
                                                <?= formatDate($ban['created_at']) ?>
                                            </td>
                                            <td>
                                                <?php if ($ban['expires_at']): ?>
                                                    <?= formatDate($ban['expires_at']) ?>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Süresiz</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                switch ($ban['ban_status']):
                                                    case 'removed': ?>
                                                        <span class="badge bg-success">
                                                            Kaldırıldı
                                                            (<?= htmlspecialchars($ban['removed_by_username'] ?? 'Bilinmiyor') ?>)
                                                        </span>
                                                        <?php break;
                                                    case 'expired': ?>
                                                        <span class="badge bg-warning">Süresi Doldu</span>
                                                        <?php break;
                                                    default: ?>
                                                        <span class="badge bg-danger">Aktif</span>
                                                <?php endswitch; ?>
                                            </td>
                                            <td>
                                                <?php if ($ban['ban_status'] === 'active'): ?>
                                                    <form action="" method="post" class="d-inline" 
                                                          onsubmit="return confirm('Bu kullanıcının yasağını kaldırmak istediğinize emin misiniz?')">
                                                        <?= getCSRFToken() ?>
                                                        <input type="hidden" name="action" value="unban">
                                                        <input type="hidden" name="user_id" value="<?= $ban['user_id'] ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            Yasağı Kaldır
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($bans)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                Yasaklı kullanıcı bulunmuyor.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require_once 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Yasaklama Modalı -->
    <div class="modal modal-blur fade" id="banModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="ban" value="1">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Kullanıcı Yasakla</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Kullanıcı</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Kullanıcı seçin</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['username']) ?> 
                                        (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Yasaklama Nedeni</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Süre</label>
                            <select name="duration" class="form-select" required>
                                <option value="1 day">1 Gün</option>
                                <option value="3 days">3 Gün</option>
                                <option value="1 week">1 Hafta</option>
                                <option value="2 weeks">2 Hafta</option>
                                <option value="1 month">1 Ay</option>
                                <option value="3 months">3 Ay</option>
                                <option value="6 months">6 Ay</option>
                                <option value="1 year">1 Yıl</option>
                                <option value="permanent">Süresiz</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                            İptal
                        </button>
                        <button type="submit" class="btn btn-danger ms-auto">
                            Yasakla
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html> 