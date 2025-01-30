<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Filtreleme
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : null;
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : null;

// SQL sorgusu
$sql = "
    SELECT l.*, u.username 
    FROM admin_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($user_id) {
    $sql .= " AND l.user_id = ?";
    $params[] = $user_id;
}

if ($action) {
    $sql .= " AND l.action = ?";
    $params[] = $action;
}

if ($date_start) {
    $sql .= " AND DATE(l.created_at) >= ?";
    $params[] = $date_start;
}

if ($date_end) {
    $sql .= " AND DATE(l.created_at) <= ?";
    $params[] = $date_end;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 100";

try {
    $db->beginTransaction();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    $db->commit();
} catch (PDOException $e) {
    $db->rollBack();
    $error = "Veritabanı hatası: " . $e->getMessage();
}

// Aksiyonları getir
try {
    $actions = $db->query("SELECT DISTINCT action FROM admin_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $actions = [];
    $error = "Aksiyon listesi alınamadı: " . $e->getMessage();
}

// Admin kullanıcıları getir
try {
    $admins = $db->query("
        SELECT DISTINCT u.* 
        FROM users u 
        JOIN user_role_relations urr ON u.id = urr.user_id 
        JOIN user_roles r ON urr.role_id = r.id 
        WHERE r.name = 'admin'
        ORDER BY u.username
    ")->fetchAll();
} catch (PDOException $e) {
    $admins = [];
    $error = "Admin listesi alınamadı: " . $e->getMessage();
}

$page_title = 'Admin Logları';
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
                    <div class="card mb-3">
                        <div class="card-body">
                            <form action="" method="get" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Admin</label>
                                    <select class="form-select" name="user_id">
                                        <option value="">Tümü</option>
                                        <?php foreach ($admins as $admin): ?>
                                        <option value="<?= $admin['id'] ?>" <?= $user_id == $admin['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($admin['username']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Aksiyon</label>
                                    <select class="form-select" name="action">
                                        <option value="">Tümü</option>
                                        <?php foreach ($actions as $act): ?>
                                        <option value="<?= $act ?>" <?= $action == $act ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($act) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Başlangıç Tarihi</label>
                                    <input type="date" class="form-control" name="date_start" value="<?= $date_start ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" name="date_end" value="<?= $date_end ?>">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        Filtrele
                                    </button>
                                    <a href="?" class="btn btn-link">
                                        Filtreleri Temizle
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Log tablosu -->
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Admin</th>
                                        <th>Aksiyon</th>
                                        <th>IP Adresi</th>
                                        <th>Tarayıcı</th>
                                        <th>Detaylar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= formatDate($log['created_at']) ?></td>
                                        <td>
                                            <a href="users.php?id=<?= $log['user_id'] ?>">
                                                <?= htmlspecialchars($log['username']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                        <td>
                                            <span class="text-muted" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                                <?= mb_substr($log['user_agent'], 0, 30) ?>...
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($log['details']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="showDetails(<?= htmlspecialchars(json_encode($log['details'])) ?>)">
                                                Detayları Göster
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require_once 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Detay Modalı -->
    <div class="modal modal-blur fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">İşlem Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="detailsContent" class="text-muted"></pre>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
    function showDetails(details) {
        const content = typeof details === 'string' ? details : JSON.stringify(details, null, 2);
        document.getElementById('detailsContent').textContent = content;
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        modal.show();
    }
    </script>
</body>
</html> 