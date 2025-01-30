<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Platform ID'si varsa platformu getir
$platform = null;
if (isset($_GET['id'])) {
    $stmt = $db->prepare("
        SELECT * FROM platforms 
        WHERE id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $platform = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$platform) {
        setFlashMessage('error', 'Platform bulunamadı.');
        redirect('admin/platforms.php');
    }
}

// Sayfa başlığı
$page_title = $platform ? 'Platform Düzenle' : 'Yeni Platform Ekle';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('error', 'CSRF token doğrulaması başarısız.');
        redirect('admin/platforms.php');
    }
    
    // Form verilerini al
    $name = trim($_POST['name']);
    $website = trim($_POST['website']);
    $logo_url = trim($_POST['logo_url']);
    
    // Validasyon
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Platform adı zorunludur.';
    }
    if (empty($website)) {
        $errors[] = 'Website adresi zorunludur.';
    }
    if (!filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Geçersiz website adresi.';
    }
    if (empty($logo_url)) {
        $errors[] = 'Logo URL zorunludur.';
    }
    if (!filter_var($logo_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Geçersiz logo URL\'si.';
    }
    
    // Hata yoksa kaydet
    if (empty($errors)) {
        try {
            if ($platform) {
                // Güncelle
                $stmt = $db->prepare("
                    UPDATE platforms 
                    SET name = ?, website = ?, logo_url = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $website, $logo_url, $platform['id']]);
                
                setFlashMessage('success', 'Platform başarıyla güncellendi.');
            } else {
                // Ekle
                $stmt = $db->prepare("
                    INSERT INTO platforms (name, website, logo_url, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$name, $website, $logo_url]);
                
                setFlashMessage('success', 'Platform başarıyla eklendi.');
            }
            
            redirect('admin/platforms.php');
        } catch (PDOException $e) {
            $errors[] = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
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
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="" method="post" class="card">
                                <div class="card-body">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    
                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?= $error ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Platform Adı</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ($platform ? htmlspecialchars($platform['name']) : '') ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Website</label>
                                        <input type="url" class="form-control" name="website" 
                                               value="<?= isset($_POST['website']) ? htmlspecialchars($_POST['website']) : ($platform ? htmlspecialchars($platform['website']) : '') ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Logo URL</label>
                                        <input type="url" class="form-control" name="logo_url" 
                                               value="<?= isset($_POST['logo_url']) ? htmlspecialchars($_POST['logo_url']) : ($platform ? htmlspecialchars($platform['logo_url']) : '') ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <a href="<?= url('admin/platforms.php') ?>" class="btn btn-link">İptal</a>
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require_once 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html> 