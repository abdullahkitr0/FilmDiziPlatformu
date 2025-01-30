<?php
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

// Sayfalama parametreleri
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Toplam yorum sayısını al
$total = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$total_pages = ceil($total / $per_page);

// Yorumları getir
$stmt = $db->prepare("
    SELECT c.*, u.username, cnt.title as content_title, cnt.id as content_id,
           cnt.type as content_type, 
           (SELECT GROUP_CONCAT(cat.name) FROM content_categories cc 
            JOIN categories cat ON cc.category_id = cat.id 
            WHERE cc.content_id = cnt.id) as category_name
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN contents cnt ON c.content_id = cnt.id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$per_page, $offset]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $contentId = $_POST['content_id']; // İçerik ID'si
    if ($_POST['action'] === 'add') {
        // İzleme listesine ekleme işlemi
        addToWatchlist($_SESSION['user_id'], $contentId);
        setFlash('success', 'İçerik izleme listesine eklendi.');
    } elseif ($_POST['action'] === 'remove') {
        // İzleme listesinden çıkarma işlemi
        removeFromWatchlist($_SESSION['user_id'], $contentId);
        setFlash('success', 'İçerik izleme listesinden çıkarıldı.');
    }
    
    // Yönlendirme
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

require_once 'includes/header.php';
?>

<div class="container-xl py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Son Yorumlar</h1>
    </div>

    <?php if ($comments): ?>
        <div class="card">
            <div class="list-group list-group-flush">
                <?php foreach ($comments as $comment): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar">
                                    <?= strtoupper(substr($comment['username'], 0, 2)) ?>
                                </span>
                            </div>
                            <div class="col text-truncate">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                        <span class="text-muted ms-2">
                                            <?= formatDate($comment['created_at']) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="badge bg-blue me-1">
                                            Puan: <?= $comment['rating'] ?>/10
                                        </span>
                                        <span class="badge bg-<?= $comment['content_type'] === 'movie' ? 'green' : 'purple' ?>">
                                            <?= $comment['content_type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                        </span>
                                    </div>
                                </div>
                                <h4 class="mb-2">
                                    <a href="content.php?id=<?= $comment['content_id'] ?>" class="text-reset">
                                        <?= htmlspecialchars($comment['content_title']) ?>
                                    </a>
                                    <?php if ($comment['category_name']): ?>
                                        <small class="text-muted">
                                            • <?= htmlspecialchars($comment['category_name']) ?>
                                        </small>
                                    <?php endif; ?>
                                </h4>
                                <div class="mt-2">
                                    <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Sayfalama">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Önceki</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Sonraki</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">Henüz yorum yapılmamış.</div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 