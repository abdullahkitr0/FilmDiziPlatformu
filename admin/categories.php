<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Sayfa başlığı
$page_title = 'Kategoriler';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    
    if ($action === 'delete' && $id) {
        try {
            $db->beginTransaction();
            
            // Önce ilişkili kayıtları sil
            $db->prepare("DELETE FROM content_categories WHERE category_id = ?")->execute([$id]);
            
            // Sonra kategoriyi sil
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $db->commit();
                setFlashMessage('Kategori başarıyla silindi.', 'success');
            } else {
                $db->rollBack();
                setFlashMessage('Kategori silinirken bir hata oluştu.', 'danger');
            }
        } catch (PDOException $e) {
            $db->rollBack();
            setFlashMessage('Kategori silinirken bir hata oluştu: ' . $e->getMessage(), 'danger');
        }
    } else {
        // Kategori ekleme/güncelleme
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'slug' => createSlug(trim($_POST['name'] ?? ''))
        ];
        
        if (empty($data['name'])) {
            setFlashMessage('Kategori adı gereklidir.', 'danger');
        } else {
            try {
                if ($id) {
                    // Güncelleme
                    $stmt = $db->prepare("
                        UPDATE categories 
                        SET name = ?, description = ?, slug = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([$data['name'], $data['description'], $data['slug'], $id]);
                } else {
                    // Yeni ekleme
                    $stmt = $db->prepare("
                        INSERT INTO categories (name, description, slug, created_at, updated_at)
                        VALUES (?, ?, ?, NOW(), NOW())
                    ");
                    $result = $stmt->execute([$data['name'], $data['description'], $data['slug']]);
                }
                
                if ($result) {
                    setFlashMessage(
                        $id ? 'Kategori güncellendi.' : 'Kategori eklendi.', 
                        'success'
                    );
                } else {
                    setFlashMessage('Bir hata oluştu.', 'danger');
                }
            } catch (PDOException $e) {
                setFlashMessage('Bir hata oluştu: ' . $e->getMessage(), 'danger');
            }
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Kategorileri getir
try {
    $categories = $db->query("
        SELECT c.*, 
               COUNT(DISTINCT cc.content_id) as content_count
        FROM categories c
        LEFT JOIN content_categories cc ON c.id = cc.category_id
        GROUP BY c.id
        ORDER BY c.name
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setFlashMessage('Kategoriler yüklenirken bir hata oluştu: ' . $e->getMessage(), 'danger');
    $categories = [];
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
                        <!-- Kategori ekleme formu -->
                        <div class="col-md-4">
                            <form action="" method="post" class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Yeni Kategori Ekle</h3>
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
                                        <label class="form-label required">Kategori Adı</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Açıklama</label>
                                        <textarea class="form-control" name="description" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Kategori listesi -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Kategoriler</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>Kategori</th>
                                                <th>Açıklama</th>
                                                <th>İçerik Sayısı</th>
                                                <th class="w-1"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td>
                                                        <?= htmlspecialchars($category['name'] ?? '') ?>
                                                    </td>
                                                    <td class="text-muted">
                                                        <?= htmlspecialchars($category['description'] ?? '') ?>
                                                    </td>
                                                    <td class="text-muted">
                                                        <?= $category['content_count'] ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-list flex-nowrap">
                                                            <a href="<?= url("admin/category-form.php?id={$category['id']}") ?>" class="btn btn-primary btn-icon">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                                    <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                                    <path d="M16 5l3 3" />
                                                                </svg>
                                                            </a>
                                                            <a href="<?= url("admin/category-delete.php?id={$category['id']}") ?>" 
                                                               class="btn btn-danger btn-icon"
                                                               onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
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