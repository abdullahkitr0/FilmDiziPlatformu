<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Sayfa başlığı
$page_title = 'Platformlar';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    
    if ($action === 'delete' && $id) {
        try {
            $db->beginTransaction();
            
            // Önce ilişkili kayıtları sil
            $db->prepare("DELETE FROM content_platform_relations WHERE platform_id = ?")->execute([$id]);
            
            // Sonra platformu sil
            $stmt = $db->prepare("DELETE FROM platforms WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $db->commit();
                setFlashMessage('Platform başarıyla silindi.', 'success');
            } else {
                $db->rollBack();
                setFlashMessage('Platform silinirken bir hata oluştu.', 'danger');
            }
        } catch (PDOException $e) {
            $db->rollBack();
            setFlashMessage('Platform silinirken bir hata oluştu: ' . $e->getMessage(), 'danger');
        }
        
        redirect('admin/platforms.php');
        exit;
    }
    
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
            $stmt = $db->prepare("
                INSERT INTO platforms (name, website, logo_url, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$name, $website, $logo_url]);
            
            setFlashMessage('Platform başarıyla eklendi.');
            redirect('admin/platforms.php');
        } catch (PDOException $e) {
            $errors[] = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Platformları getir
$platforms = $db->query("
    SELECT p.*, COUNT(cpr.content_id) as content_count 
    FROM platforms p
    LEFT JOIN content_platform_relations cpr ON p.id = cpr.platform_id
    GROUP BY p.id
    ORDER BY p.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

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
                        <!-- Platform ekleme formu -->
                        <div class="col-md-4">
                            <form action="" method="post" class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Yeni Platform Ekle</h3>
                                </div>
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
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Website</label>
                                        <input type="url" class="form-control" name="website" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Logo URL</label>
                                        <input type="url" class="form-control" name="logo_url" required>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Platform listesi -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Platformlar</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>Logo</th>
                                                <th>Platform</th>
                                                <th>Website</th>
                                                <th>İçerik Sayısı</th>
                                                <th class="w-1"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($platforms as $platform): ?>
                                                <tr>
                                                    <td>
                                                        <img src="<?= htmlspecialchars($platform['logo_url']) ?>" 
                                                             alt="<?= htmlspecialchars($platform['name']) ?>"
                                                             class="avatar avatar-md">
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($platform['name']) ?>
                                                    </td>
                                                    <td>
                                                        <a href="<?= htmlspecialchars($platform['website']) ?>" 
                                                           target="_blank" 
                                                           class="text-reset">
                                                            <?= htmlspecialchars($platform['website']) ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <?= $platform['content_count'] ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-list flex-nowrap">
                                                            <a href="<?= url("admin/platform-form.php?id={$platform['id']}") ?>" class="btn btn-primary btn-icon">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                                    <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                                    <path d="M16 5l3 3" />
                                                                </svg>
                                                            </a>
                                                            <a href="<?= url("admin/platform-delete.php?id={$platform['id']}") ?>" 
                                                               class="btn btn-danger btn-icon"
                                                               onclick="return confirm('Bu platformu silmek istediğinize emin misiniz?')">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                    <path d="M4 7l16 0" />
                                                                    <path d="M10 11l0 6" />
                                                                    <path d="M14 11l0 6" />
                                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                                </svg>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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