<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Sayfa başlığı
$page_title = 'Yorumlar';

// Yorumları getir
$comments = $db->query("
    SELECT c.*, 
           u.username, 
           cnt.title as content_title
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN contents cnt ON c.content_id = cnt.id
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
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
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Kullanıcı</th>
                                        <th>İçerik</th>
                                        <th>Yorum</th>
                                        <th>Tarih</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comments as $comment): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($comment['username']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($comment['content_title']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($comment['comment']) ?>
                                            </td>
                                            <td class="text-muted">
                                                <?= formatDate($comment['created_at']) ?>
                                            </td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    <a href="<?= url("admin/comment-delete.php?id={$comment['id']}") ?>" 
                                                       class="btn btn-danger btn-icon"
                                                       onclick="return confirm('Bu yorumu silmek istediğinize emin misiniz?')">
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
            
            <?php require_once 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html> 