<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Sayfa başlığı
$page_title = 'SEO Ayarları';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF kontrolü
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('CSRF token doğrulaması başarısız.');
        }
        
        $db->beginTransaction();
        
        // Site başlığı
        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_by, updated_at)
            VALUES ('site_title', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$_POST['site_title'], $_SESSION['user_id']]);
        
        // Site açıklaması
        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_by, updated_at)
            VALUES ('site_description', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$_POST['site_description'], $_SESSION['user_id']]);
        
        // Site anahtar kelimeleri
        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_by, updated_at)
            VALUES ('site_keywords', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$_POST['site_keywords'], $_SESSION['user_id']]);
        
        // Google Analytics kodu
        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_by, updated_at)
            VALUES ('google_analytics', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$_POST['google_analytics'], $_SESSION['user_id']]);
        
        // robots.txt içeriği
        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_by, updated_at)
            VALUES ('robots_txt', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$_POST['robots_txt'], $_SESSION['user_id']]);
        
        // robots.txt dosyasını güncelle
        $robots_content = trim($_POST['robots_txt']);
        if (!empty($robots_content)) {
            file_put_contents('../robots.txt', $robots_content);
        }
        
        $db->commit();
        setFlashMessage('SEO ayarları başarıyla güncellendi.', 'success');
    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
    
    redirect('admin/seo-settings.php');
    exit;
}

// Mevcut ayarları getir
try {
    $settings = [];
    $stmt = $db->prepare("
        SELECT s.*, u.username as updated_by_username
        FROM site_settings s
        LEFT JOIN users u ON s.updated_by = u.id
        WHERE s.setting_key IN ('site_title', 'site_description', 'site_keywords', 'google_analytics', 'robots_txt')
    ");
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row;
    }
} catch (PDOException $e) {
    setFlashMessage('Ayarlar yüklenirken bir hata oluştu: ' . $e->getMessage(), 'danger');
    $settings = [];
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
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <form action="" method="post" class="card">
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Site Başlığı</label>
                                        <input type="text" 
                                               class="form-control" 
                                               name="site_title" 
                                               value="<?= htmlspecialchars($settings['site_title']['setting_value'] ?? '') ?>" 
                                               required>
                                        <?php if (isset($settings['site_title'])): ?>
                                            <small class="form-hint">
                                                Son güncelleme: <?= formatDate($settings['site_title']['updated_at']) ?> 
                                                (<?= htmlspecialchars($settings['site_title']['updated_by_username'] ?? 'Bilinmiyor') ?>)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Site Açıklaması</label>
                                        <textarea class="form-control" 
                                                  name="site_description" 
                                                  rows="3" 
                                                  required><?= htmlspecialchars($settings['site_description']['setting_value'] ?? '') ?></textarea>
                                        <?php if (isset($settings['site_description'])): ?>
                                            <small class="form-hint">
                                                Son güncelleme: <?= formatDate($settings['site_description']['updated_at']) ?> 
                                                (<?= htmlspecialchars($settings['site_description']['updated_by_username'] ?? 'Bilinmiyor') ?>)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Site Anahtar Kelimeleri</label>
                                <textarea class="form-control" 
                                          name="site_keywords" 
                                          rows="3"><?= htmlspecialchars($settings['site_keywords']['setting_value'] ?? '') ?></textarea>
                                <small class="form-hint">Virgülle ayırarak yazın.</small>
                                <?php if (isset($settings['site_keywords'])): ?>
                                    <small class="form-hint">
                                        Son güncelleme: <?= formatDate($settings['site_keywords']['updated_at']) ?> 
                                        (<?= htmlspecialchars($settings['site_keywords']['updated_by_username'] ?? 'Bilinmiyor') ?>)
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Google Analytics Kodu</label>
                                <textarea class="form-control" 
                                          name="google_analytics" 
                                          rows="5"><?= htmlspecialchars($settings['google_analytics']['setting_value'] ?? '') ?></textarea>
                                <?php if (isset($settings['google_analytics'])): ?>
                                    <small class="form-hint">
                                        Son güncelleme: <?= formatDate($settings['google_analytics']['updated_at']) ?> 
                                        (<?= htmlspecialchars($settings['google_analytics']['updated_by_username'] ?? 'Bilinmiyor') ?>)
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">robots.txt İçeriği</label>
                                <textarea class="form-control font-monospace" 
                                          name="robots_txt" 
                                          rows="10"><?= htmlspecialchars($settings['robots_txt']['setting_value'] ?? '') ?></textarea>
                                <?php if (isset($settings['robots_txt'])): ?>
                                    <small class="form-hint">
                                        Son güncelleme: <?= formatDate($settings['robots_txt']['updated_at']) ?> 
                                        (<?= htmlspecialchars($settings['robots_txt']['updated_by_username'] ?? 'Bilinmiyor') ?>)
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php require_once 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html> 