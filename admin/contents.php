<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// İçerik silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $db->beginTransaction();
            
            // Önce ilişkili kayıtları sil
            $db->prepare("DELETE FROM content_categories WHERE content_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM content_platforms WHERE content_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM comments WHERE content_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM watchlist WHERE content_id = ?")->execute([$id]);
            
            // Sonra içeriği sil
            $stmt = $db->prepare("DELETE FROM contents WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $db->commit();
                setFlashMessage('İçerik başarıyla silindi.', 'success');
            }
        } catch (PDOException $e) {
            $db->rollBack();
            setFlashMessage('İçerik silinirken bir hata oluştu: ' . $e->getMessage(), 'danger');
        }
    }
}

// Filtreleme parametreleri
$type = $_GET['type'] ?? '';
$category = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');

// Kategorileri getir
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// İçerikleri getir
$params = [];
$sql = "SELECT c.*, 
               GROUP_CONCAT(DISTINCT cat.name) as category_name
        FROM contents c 
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id 
        WHERE 1=1";

if ($type) {
    $sql .= " AND c.type = ?";
    $params[] = $type;
}

if ($category) {
    $sql .= " AND cc.category_id = ?";
    $params[] = $category;
}

if ($search) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sayfa başlığı
$page_title = 'İçerikler';
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
                            <div class="btn-list">
                                <a href="<?= url('admin/content-form.php') ?>" class="btn btn-primary d-none d-sm-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 5l0 14" />
                                        <path d="M5 12l14 0" />
                                    </svg>
                                    Yeni İçerik Ekle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <!-- Filtreler -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Arama</label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="search" 
                                           value="<?= htmlspecialchars($search) ?>"
                                           placeholder="İçerik adı veya açıklama...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tür</label>
                                    <select name="type" class="form-select">
                                        <option value="">Tümü</option>
                                        <option value="movie" <?= $type === 'movie' ? 'selected' : '' ?>>Film</option>
                                        <option value="series" <?= $type === 'series' ? 'selected' : '' ?>>Dizi</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Kategori</label>
                                    <select name="category" class="form-select">
                                        <option value="">Tümü</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                    <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        Filtrele
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- İçerik listesi -->
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Poster</th>
                                        <th>Başlık</th>
                                        <th>Tür</th>
                                        <th>Kategori</th>
                                        <th>IMDB</th>
                                        <th>Yıl</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contents as $content): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                                                     alt="" 
                                                     class="avatar avatar-md">
                                            </td>
                                            <td>
                                                <div class="font-weight-medium">
                                                    <?= htmlspecialchars($content['title']) ?>
                                                </div>
                                            </td>
                                            <td class="text-muted">
                                                <?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                            </td>
                                            <td class="text-muted">
                                                <?= htmlspecialchars($content['category_name'] ?? '-') ?>
                                            </td>
                                            <td class="text-muted">
                                                <?= number_format($content['imdb_rating'], 1) ?>
                                            </td>
                                            <td class="text-muted">
                                                <?= $content['release_year'] ?>
                                            </td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    <a href="/4/admin/content-form.php?id=<?= $content['id'] ?>" 
                                                       class="btn btn-icon btn-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                                            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                                            <path d="M16 5l3 3"></path>
                                                        </svg>
                                                    </a>
                                                    <form method="POST" 
                                                          action="" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('Bu içeriği silmek istediğinize emin misiniz?')">
                                                        <input type="hidden" name="id" value="<?= $content['id'] ?>">
                                                        <button type="submit" 
                                                                name="delete" 
                                                                class="btn btn-icon btn-danger">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                <path d="M4 7l16 0"></path>
                                                                <path d="M10 11l0 6"></path>
                                                                <path d="M14 11l0 6"></path>
                                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (!$contents): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                İçerik bulunamadı.
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
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html> 