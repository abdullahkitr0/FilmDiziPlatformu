<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kategori slug'ını al
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    redirect();
}

try {
    // Kategoriyi getir
    $stmt = $db->prepare("
        SELECT * 
        FROM categories 
        WHERE slug = ?
    ");
    $stmt->execute([$slug]);
    $category = $stmt->fetch();
    
    if (!$category) {
        setFlashMessage('Kategori bulunamadı.', 'danger');
        redirect();
    }
    
    $page_title = $category['name'];
    $page_description = $category['description'] ?? "Film ve dizi kategorisi: {$category['name']}";
    
    // Sayfalama
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = PER_PAGE;
    $offset = ($page - 1) * $per_page;
    
    // Filtreleme
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $valid_types = ['movie', 'series'];
    if (!in_array($type, $valid_types)) {
        $type = '';
    }
    
    // Sıralama
    $sort = $_GET['sort'] ?? '';
    $valid_sorts = ['title_asc', 'title_desc', 'year_desc', 'year_asc', 'rating_desc', 'rating_asc'];
    if (!in_array($sort, $valid_sorts)) {
        $sort = 'created_at_desc';
    }
    
    // WHERE koşulları
    $where = ['cc.category_id = ?'];
    $params = [$category['id']];
    
    if ($type) {
        $where[] = 'c.type = ?';
        $params[] = $type;
    }
    
    $where_clause = implode(' AND ', $where);
    
    // Toplam içerik sayısını al
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT c.id)
        FROM contents c
        JOIN content_categories cc ON c.id = cc.content_id
        WHERE {$where_clause}
    ");
    $stmt->execute($params);
    $total_items = $stmt->fetchColumn();
    
    // Sayfalama bilgilerini hesapla
    $total_pages = ceil($total_items / $per_page);
    $page = max(1, min($page, $total_pages));
    
    // Sıralama
    $order_by = match($sort) {
        'title_asc' => 'c.title ASC',
        'title_desc' => 'c.title DESC',
        'year_asc' => 'c.release_year ASC',
        'year_desc' => 'c.release_year DESC',
        'rating_desc' => 'c.imdb_rating DESC',
        'rating_asc' => 'c.imdb_rating ASC',
        default => 'c.created_at DESC'
    };
    
    // İçerikleri getir
    $stmt = $db->prepare("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT cat.name) as categories,
               COALESCE(AVG(cm.rating), 0) as avg_rating,
               COUNT(DISTINCT cm.id) as rating_count
        FROM contents c
        JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN content_categories cc2 ON c.id = cc2.content_id
        LEFT JOIN categories cat ON cc2.category_id = cat.id
        LEFT JOIN comments cm ON c.id = cm.content_id
        WHERE {$where_clause}
        GROUP BY c.id
        ORDER BY {$order_by}
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $contents = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('Veriler yüklenirken bir hata oluştu: ' . $e->getMessage(), 'danger');
    redirect();
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-folder me-2"></i><?= htmlspecialchars($category['name']) ?>
            </h1>
            <?php if ($category['description']): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars($category['description']) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="d-flex gap-2">
            <div class="btn-group">
                <a href="<?= url("category.php?slug={$category['slug']}") ?>" class="btn btn-<?= $type ? 'outline-' : '' ?>primary">
                    Tümü
                </a>
                <a href="<?= url("category.php?slug={$category['slug']}&type=movie") ?>" class="btn btn-<?= $type === 'movie' ? '' : 'outline-' ?>primary">
                    Filmler
                </a>
                <a href="<?= url("category.php?slug={$category['slug']}&type=series") ?>" class="btn btn-<?= $type === 'series' ? '' : 'outline-' ?>primary">
                    Diziler
                </a>
            </div>
            
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-sort me-2"></i>Sırala
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item <?= $sort === 'title_asc' ? 'active' : '' ?>" 
                           href="<?= url("category.php?slug={$category['slug']}&type={$type}&sort=title_asc") ?>">
                            İsim (A-Z)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= $sort === 'title_desc' ? 'active' : '' ?>" 
                           href="<?= url("category.php?slug={$category['slug']}&type={$type}&sort=title_desc") ?>">
                            İsim (Z-A)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= $sort === 'year_desc' ? 'active' : '' ?>" 
                           href="<?= url("category.php?slug={$category['slug']}&type={$type}&sort=year_desc") ?>">
                            Yıl (Yeni-Eski)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= $sort === 'year_asc' ? 'active' : '' ?>" 
                           href="<?= url("category.php?slug={$category['slug']}&type={$type}&sort=year_asc") ?>">
                            Yıl (Eski-Yeni)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= $sort === 'rating_desc' ? 'active' : '' ?>" 
                           href="<?= url("category.php?slug={$category['slug']}&type={$type}&sort=rating_desc") ?>">
                            Puan (Yüksek-Düşük)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= $sort === 'rating_asc' ? 'active' : '' ?>" 
                           href="<?= url("category.php?slug={$category['slug']}&type={$type}&sort=rating_asc") ?>">
                            Puan (Düşük-Yüksek)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php if (!empty($contents)): ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($contents as $content): ?>
                <div class="col">
                    <div class="card h-100">
                        <a href="<?= url("content.php?slug=" . $content['slug']) ?>" class="card-img-top-wrapper">
                            <img src="<?= htmlspecialchars($content['poster_url']) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($content['title']) ?>">
                        </a>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="<?= url("content.php?slug=" . $content['slug']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($content['title']) ?>
                                </a>
                            </h5>
                            <p class="card-text small text-muted">
                                <?= $content['categories'] ? htmlspecialchars($content['categories']) : 'Kategorisiz' ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary">
                                    <?= $content['type'] === 'movie' ? 'Film' : 'Dizi' ?>
                                </span>
                                <div>
                                    <?php if ($content['imdb_rating']): ?>
                                    <span class="text-warning me-2" title="IMDb Puanı">
                                        <i class="fas fa-star me-1"></i><?= number_format($content['imdb_rating'], 1) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($content['avg_rating'] > 0): ?>
                                    <span class="text-success" title="Kullanıcı Puanı (<?= $content['rating_count'] ?> oy)">
                                        <i class="fas fa-users me-1"></i><?= number_format($content['avg_rating'], 1) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i><?= $content['release_year'] ?>
                                </small>
                                <?php if (isLoggedIn()): ?>
                                    <?php if (!isInWatchlist($content['id'])): ?>
                                        <form method="POST" action="<?= url('watchlist.php') ?>" class="d-inline">
                                            <?= getCSRFToken() ?>
                                            <input type="hidden" name="content_id" value="<?= $content['id'] ?>">
                                            <button type="submit" name="action" value="add" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus me-1"></i>Listeye Ekle
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Listede
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= url("category.php?slug={$category['slug']}&page=" . ($page - 1) . ($type ? "&type={$type}" : "") . ($sort ? "&sort={$sort}" : "")) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="' . url("category.php?slug={$category['slug']}&page=1" . ($type ? "&type={$type}" : "") . ($sort ? "&sort={$sort}" : "")) . '">1</a></li>';
                        if ($start > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    for ($i = $start; $i <= $end; $i++) {
                        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                        echo '<a class="page-link" href="' . url("category.php?slug={$category['slug']}&page={$i}" . ($type ? "&type={$type}" : "") . ($sort ? "&sort={$sort}" : "")) . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . url("category.php?slug={$category['slug']}&page={$total_pages}" . ($type ? "&type={$type}" : "") . ($sort ? "&sort={$sort}" : "")) . '">' . $total_pages . '</a></li>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= url("category.php?slug={$category['slug']}&page=" . ($page + 1) . ($type ? "&type={$type}" : "") . ($sort ? "&sort={$sort}" : "")) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-folder-open fa-3x text-muted"></i>
            </div>
            <h2 class="h4 mb-3">İçerik Bulunamadı</h2>
            <p class="text-muted mb-4">
                Bu kategoride henüz içerik bulunmuyor.
            </p>
            <a href="<?= url() ?>" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 