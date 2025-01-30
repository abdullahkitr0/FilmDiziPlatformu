<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Ayarları kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF kontrolü
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('CSRF token doğrulaması başarısız.');
        }
        
        // Genel ayarlar
        updateSetting('site_name', $_POST['site_name'], 'general', true);
        updateSetting('site_description', $_POST['site_description'], 'general', true);
        updateSetting('contact_email', $_POST['contact_email'], 'general', true);
        
        // Sosyal medya ayarları
        updateSetting('facebook_url', $_POST['facebook_url'], 'social', true);
        updateSetting('twitter_url', $_POST['twitter_url'], 'social', true);
        updateSetting('instagram_url', $_POST['instagram_url'], 'social', true);
        
        // E-posta ayarları
        updateSetting('smtp_host', $_POST['smtp_host'], 'email');
        updateSetting('smtp_port', $_POST['smtp_port'], 'email');
        updateSetting('smtp_username', $_POST['smtp_username'], 'email');
        updateSetting('smtp_password', $_POST['smtp_password'], 'email');
        
        // Diğer ayarlar
        updateSetting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0', 'system');
        updateSetting('user_registration', isset($_POST['user_registration']) ? '1' : '0', 'system');
        updateSetting('comment_approval', isset($_POST['comment_approval']) ? '1' : '0', 'system');
        
        setFlashMessage('success', 'Ayarlar başarıyla kaydedildi.');
    } catch (Exception $e) {
        setFlashMessage('error', 'Bir hata oluştu: ' . $e->getMessage());
    }
    
    redirect('admin/settings.php');
}

$page_title = 'Site Ayarları';
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
                        <?= getCSRFToken() ?>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Genel Ayarlar -->
                                    <h3 class="card-title">Genel Ayarlar</h3>
                                    <div class="mb-3">
                                        <label class="form-label required">Site Adı</label>
                                        <input type="text" class="form-control" name="site_name" 
                                               value="<?= htmlspecialchars(getSetting('site_name') ?? '') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Site Açıklaması</label>
                                        <textarea class="form-control" name="site_description" rows="3"><?= htmlspecialchars(getSetting('site_description') ?? '') ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label required">İletişim E-posta</label>
                                        <input type="email" class="form-control" name="contact_email" 
                                               value="<?= htmlspecialchars(getSetting('contact_email') ?? '') ?>" required>
                                    </div>
                                    
                                    <!-- Sosyal Medya -->
                                    <h3 class="card-title mt-4">Sosyal Medya</h3>
                                    <div class="mb-3">
                                        <label class="form-label">Facebook URL</label>
                                        <input type="url" class="form-control" name="facebook_url" 
                                               value="<?= htmlspecialchars(getSetting('facebook_url') ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Twitter URL</label>
                                        <input type="url" class="form-control" name="twitter_url" 
                                               value="<?= htmlspecialchars(getSetting('twitter_url') ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Instagram URL</label>
                                        <input type="url" class="form-control" name="instagram_url" 
                                               value="<?= htmlspecialchars(getSetting('instagram_url') ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- E-posta Ayarları -->
                                    <h3 class="card-title">E-posta Ayarları</h3>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Sunucu</label>
                                        <input type="text" class="form-control" name="smtp_host" 
                                               value="<?= htmlspecialchars(getSetting('smtp_host') ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" name="smtp_port" 
                                               value="<?= htmlspecialchars(getSetting('smtp_port') ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Kullanıcı Adı</label>
                                        <input type="text" class="form-control" name="smtp_username" 
                                               value="<?= htmlspecialchars(getSetting('smtp_username') ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Şifre</label>
                                        <input type="password" class="form-control" name="smtp_password" 
                                               value="<?= htmlspecialchars(getSetting('smtp_password') ?? '') ?>">
                                    </div>
                                    
                                    <!-- Diğer Ayarlar -->
                                    <h3 class="card-title mt-4">Diğer Ayarlar</h3>
                                    <div class="mb-3">
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                                   <?= getSetting('maintenance_mode') ? 'checked' : '' ?>>
                                            <span class="form-check-label">Bakım Modu</span>
                                        </label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="user_registration"
                                                   <?= getSetting('user_registration') ? 'checked' : '' ?>>
                                            <span class="form-check-label">Kullanıcı Kaydı</span>
                                        </label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="comment_approval"
                                                   <?= getSetting('comment_approval') ? 'checked' : '' ?>>
                                            <span class="form-check-label">Yorum Onayı</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                Ayarları Kaydet
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