<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Kategori ID'si varsa kategoriyi getir
$category = null;
if (isset($_GET['id'])) {
    $stmt = $db->prepare("
        SELECT * FROM categories 
        WHERE id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        setFlashMessage('error', 'Kategori bulunamadı.');
        redirect('admin/categories.php');
    }
}

// Sayfa başlığı
$page_title = $category ? 'Kategori Düzenle' : 'Yeni Kategori Ekle';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('error', 'CSRF token doğrulaması başarısız.');
        redirect('admin/categories.php');
    }
    
    // Form verilerini al
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    // Validasyon
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Kategori adı zorunludur.';
    }
    
    // Hata yoksa kaydet
    if (empty($errors)) {
        try {
            if ($category) {
                // Güncelle
                $stmt = $db->prepare("
                    UPDATE categories 
                    SET name = ?, description = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $category['id']]);
                
                setFlashMessage('success', 'Kategori başarıyla güncellendi.');
            } else {
                // Ekle
                $stmt = $db->prepare("
                    INSERT INTO categories (name, description, created_at, updated_at)
                    VALUES (?, ?, NOW(), NOW())
                ");
                $stmt->execute([$name, $description]);
                
                setFlashMessage('success', 'Kategori başarıyla eklendi.');
            }
            
            redirect('admin/categories.php');
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
                                        <label class="form-label required">Kategori Adı</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ($category ? htmlspecialchars($category['name']) : '') ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Açıklama</label>
                                        <textarea class="form-control" name="description" rows="3"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ($category ? htmlspecialchars($category['description']) : '') ?></textarea>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <a href="<?= url('admin/categories.php') ?>" class="btn btn-link">İptal</a>
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