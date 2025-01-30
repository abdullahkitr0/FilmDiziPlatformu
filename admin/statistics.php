<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Zaman aralığı
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';
$allowed_periods = ['daily', 'weekly', 'monthly'];
if (!in_array($period, $allowed_periods)) {
    $period = 'daily';
}

// Ziyaretçi istatistikleri
$visitor_stats = getVisitorStats($period);

// En çok izlenen içerikler
$most_viewed = getMostViewedContents(10, match($period) {
    'daily' => 1,
    'weekly' => 7,
    'monthly' => 30,
    default => null
});

// En aktif kullanıcılar
$most_active = getMostActiveUsers(10, match($period) {
    'daily' => 1,
    'weekly' => 7,
    'monthly' => 30,
    default => null
});

$page_title = 'İstatistikler';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin Paneli</title>
    <!-- Tabler.io CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <div class="btn-group">
                                <a href="?period=daily" class="btn <?= $period === 'daily' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    Günlük
                                </a>
                                <a href="?period=weekly" class="btn <?= $period === 'weekly' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    Haftalık
                                </a>
                                <a href="?period=monthly" class="btn <?= $period === 'monthly' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    Aylık
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row row-deck row-cards">
                        <!-- Ziyaretçi grafiği -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Ziyaretçi İstatistikleri</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="visitorChart" style="height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- En çok izlenen içerikler -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">En Çok İzlenen İçerikler</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>İçerik</th>
                                                <th>Tür</th>
                                                <th>İzlenme</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($most_viewed as $content): ?>
                                            <tr>
                                                <td>
                                                    <a href="content-preview.php?id=<?= $content['id'] ?>">
                                                        <?= htmlspecialchars($content['title']) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                                </td>
                                                <td><?= number_format($content['view_count']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- En aktif kullanıcılar -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">En Aktif Kullanıcılar</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>Kullanıcı</th>
                                                <th>Aktivite</th>
                                                <th>Yorum</th>
                                                <th>İzleme</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($most_active as $user): ?>
                                            <tr>
                                                <td>
                                                    <a href="users.php?id=<?= $user['id'] ?>">
                                                        <?= htmlspecialchars($user['username']) ?>
                                                    </a>
                                                </td>
                                                <td><?= number_format($user['activity_count']) ?></td>
                                                <td><?= number_format($user['comment_count']) ?></td>
                                                <td><?= number_format($user['view_count']) ?></td>
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
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
    // Ziyaretçi grafiği
    const ctx = document.getElementById('visitorChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($visitor_stats, 'date')) ?>,
            datasets: [
                {
                    label: 'Tekil Ziyaretçi',
                    data: <?= json_encode(array_column($visitor_stats, 'unique_visitors')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                },
                {
                    label: 'Toplam Ziyaret',
                    data: <?= json_encode(array_column($visitor_stats, 'total_visits')) ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>
</html> 