<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Filtreleme
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$activity_type = isset($_GET['type']) ? $_GET['type'] : null;
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : null;
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : null;

// SQL sorgusu
$sql = "
    SELECT a.*, u.username 
    FROM user_activities a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($user_id) {
    $sql .= " AND a.user_id = ?";
    $params[] = $user_id;
}

if ($activity_type) {
    $sql .= " AND a.activity_type = ?";
    $params[] = $activity_type;
}

if ($date_start) {
    $sql .= " AND DATE(a.created_at) >= ?";
    $params[] = $date_start;
}

if ($date_end) {
    $sql .= " AND DATE(a.created_at) <= ?";
    $params[] = $date_end;
}

$sql .= " ORDER BY a.created_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Aktivite türlerini getir
$types = $db->query("SELECT DISTINCT activity_type FROM user_activities")->fetchAll(PDO::FETCH_COLUMN);

// Kullanıcıları getir
$users = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

$page_title = 'Aktivite Logları';
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
                                    <label class="form-label">Kullanıcı</label>
                                    <select class="form-select" name="user_id">
                                        <option value="">Tümü</option>
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= $user_id == $user['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['username']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Aktivite Türü</label>
                                    <select class="form-select" name="type">
                                        <option value="">Tümü</option>
                                        <?php foreach ($types as $type): ?>
                                        <option value="<?= $type ?>" <?= $activity_type == $type ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type) ?>
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
                                        <th>Kullanıcı</th>
                                        <th>Aktivite</th>
                                        <th>Detaylar</th>
                                        <th>IP Adresi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?= formatDate($activity['created_at']) ?></td>
                                        <td>
                                            <?php if ($activity['user_id']): ?>
                                            <a href="users.php?id=<?= $activity['user_id'] ?>">
                                                <?= htmlspecialchars($activity['username']) ?>
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted">Misafir</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['activity_type']) ?></td>
                                        <td>
                                            <?php if ($activity['activity_details']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="showDetails(<?= htmlspecialchars(json_encode($activity['activity_details'])) ?>)">
                                                Detayları Göster
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['ip_address']) ?></td>
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
                    <h5 class="modal-title">Aktivite Detayları</h5>
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