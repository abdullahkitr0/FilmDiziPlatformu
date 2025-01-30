<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Kullanıcı silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id) {
        // Önce ilişkili kayıtları sil
        $db->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM watchlist WHERE user_id = ?")->execute([$id]);
        
        // Sonra kullanıcıyı sil
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            setFlashMessage('Kullanıcı başarıyla silindi.', 'success');
        }
    }
}

// Admin yetkisi değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_admin'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $db->beginTransaction();
            
            // Kullanıcının admin rolüne sahip olup olmadığını kontrol et
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM user_role_relations ur
                JOIN user_roles r ON ur.role_id = r.id
                WHERE ur.user_id = ? AND r.name = 'admin'
            ");
            $stmt->execute([$id]);
            $isAdmin = $stmt->fetchColumn() > 0;
            
            if ($isAdmin) {
                // Admin rolünü kaldır
                $stmt = $db->prepare("
                    DELETE ur FROM user_role_relations ur
                    JOIN user_roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ? AND r.name = 'admin'
                ");
                $stmt->execute([$id]);
            } else {
                // Admin rolünü ekle
                $stmt = $db->prepare("
                    INSERT INTO user_role_relations (user_id, role_id, assigned_by)
                    SELECT ?, id, ? FROM user_roles WHERE name = 'admin'
                ");
                $stmt->execute([$id, $_SESSION['user_id']]);
            }
            
            $db->commit();
            setFlashMessage('Kullanıcı yetkisi güncellendi.', 'success');
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('Bir hata oluştu: ' . $e->getMessage(), 'danger');
        }
    }
}

// Filtreleme parametreleri
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';

// Kullanıcıları getir
$params = [];
$sql = "SELECT u.*, 
               COUNT(DISTINCT c.id) as comment_count,
               COUNT(DISTINCT w.id) as watchlist_count,
               GROUP_CONCAT(DISTINCT r.name) as roles
        FROM users u 
        LEFT JOIN comments c ON u.id = c.user_id 
        LEFT JOIN watchlist w ON u.id = w.user_id 
        LEFT JOIN user_role_relations urr ON u.id = urr.user_id
        LEFT JOIN user_roles r ON urr.role_id = r.id
        WHERE 1=1";

if ($search) {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role === 'admin') {
    $sql .= " AND EXISTS (
        SELECT 1 FROM user_role_relations ur2 
        JOIN user_roles r2 ON ur2.role_id = r2.id 
        WHERE ur2.user_id = u.id AND r2.name = 'admin'
    )";
} elseif ($role === 'user') {
    $sql .= " AND NOT EXISTS (
        SELECT 1 FROM user_role_relations ur2 
        JOIN user_roles r2 ON ur2.role_id = r2.id 
        WHERE ur2.user_id = u.id AND r2.name = 'admin'
    )";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rolleri getir
$roles = $db->query("
    SELECT * FROM user_roles 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Sayfa başlığı
$page_title = 'Kullanıcılar';
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
                    <!-- Filtreler -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Arama</label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="search" 
                                           value="<?= htmlspecialchars($search) ?>"
                                           placeholder="Kullanıcı adı veya e-posta...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Rol</label>
                                    <select name="role" class="form-select">
                                        <option value="">Tümü</option>
                                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Kullanıcı</option>
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
                    
                    <!-- Kullanıcı listesi -->
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Kullanıcı</th>
                                        <th>Roller</th>
                                        <th>Yorum Sayısı</th>
                                        <th>İzleme Listesi</th>
                                        <th>Kayıt Tarihi</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex py-1 align-items-center">
                                                    <div class="flex-fill">
                                                        <div class="font-weight-medium">
                                                            <?= htmlspecialchars($user['username']) ?>
                                                        </div>
                                                        <div class="text-muted">
                                                            <?= htmlspecialchars($user['email']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($user['roles']): ?>
                                                    <?php foreach (explode(',', $user['roles']) as $role): ?>
                                                        <span class="badge bg-blue me-1">
                                                            <?= htmlspecialchars($role) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        Kullanıcı
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $user['comment_count'] ?>
                                            </td>
                                            <td>
                                                <?= $user['watchlist_count'] ?>
                                            </td>
                                            <td class="text-muted">
                                                <?= formatDate($user['created_at']) ?>
                                            </td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    <a href="<?= url("admin/user-form.php?id={$user['id']}") ?>" class="btn btn-primary btn-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                            <path d="M16 5l3 3" />
                                                        </svg>
                                                    </a>
                                                    <a href="<?= url("admin/user-delete.php?id={$user['id']}") ?>" 
                                                       class="btn btn-danger btn-icon"
                                                       onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
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
                                    
                                    <?php if (!$users): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                Kullanıcı bulunamadı.
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