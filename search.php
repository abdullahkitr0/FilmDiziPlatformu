<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'İçerik Ara';
$page_description = 'Film ve dizi arama';

// Arama parametreleri
$search = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$category = (int)($_GET['category'] ?? 0);
$year = (int)($_GET['year'] ?? 0);
$sort = $_GET['sort'] ?? '';

// Sayfalama
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = PER_PAGE;
$offset = ($page - 1) * $per_page;

// Kategorileri getir
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Yılları getir (son 100 yıl)
$current_year = (int)date('Y');
$years = range($current_year, $current_year - 100);

// Sıralama seçenekleri
$sort_options = [
    'title_asc' => 'İsim (A-Z)',
    'title_desc' => 'İsim (Z-A)',
    'year_desc' => 'Yıl (Yeni-Eski)',
    'year_asc' => 'Yıl (Eski-Yeni)',
    'rating_desc' => 'Puan (Yüksek-Düşük)',
    'rating_asc' => 'Puan (Düşük-Yüksek)'
];

// Arama yap
$contents = [];
$total_items = 0;

if (!empty($search) || !empty($type) || !empty($category) || !empty($year)) {
    try {
        // WHERE koşullarını oluştur
        $where = ['1=1'];
        $params = [];
        
        if (!empty($search)) {
            $where[] = '(c.title LIKE ? OR c.original_title LIKE ? OR c.description LIKE ?)';
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
        }
        
        if (!empty($type)) {
            $where[] = 'c.type = ?';
            $params[] = $type;
        }
        
        if (!empty($category)) {
            $where[] = 'cc.category_id = ?';
            $params[] = $category;
        }
        
        if (!empty($year)) {
            $where[] = 'c.release_year = ?';
            $params[] = $year;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Toplam sonuç sayısını al
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT c.id)
            FROM contents c
            LEFT JOIN content_categories cc ON c.id = cc.content_id
            WHERE {$where_clause}
        ");
        $stmt->execute($params);
        $total_items = $stmt->fetchColumn();
        
        // Sayfalama
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
            LEFT JOIN content_categories cc ON c.id = cc.content_id
            LEFT JOIN categories cat ON cc.category_id = cat.id
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
        setFlashMessage('Arama yapılırken bir hata oluştu: ' . $e->getMessage(), 'danger');
    }
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Filtreler -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Filtreler</h5>
                    <form action="" method="GET" class="mb-0">
                        <div class="mb-3">
                            <label class="form-label">Arama</label>
                            <input type="text" 
                                   name="q" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Film veya dizi ara...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tür</label>
                            <select name="type" class="form-select">
                                <option value="">Tümü</option>
                                <option value="movie" <?= $type === 'movie' ? 'selected' : '' ?>>Film</option>
                                <option value="series" <?= $type === 'series' ? 'selected' : '' ?>>Dizi</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="category" class="form-select">
                                <option value="">Tümü</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Yıl</label>
                            <select name="year" class="form-select">
                                <option value="">Tümü</option>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Sıralama</label>
                            <select name="sort" class="form-select">
                                <?php foreach ($sort_options as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Ara
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sonuçlar -->
        <div class="col-md-9">
            <?php if (!empty($search) || !empty($type) || !empty($category) || !empty($year)): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2 mb-0">
                        <?php if (!empty($search)): ?>
                            "<?= htmlspecialchars($search) ?>" için sonuçlar
                        <?php else: ?>
                            Arama Sonuçları
                        <?php endif; ?>
                        <small class="text-muted">(<?= number_format($total_items) ?> sonuç)</small>
                    </h1>
                    
                    <?php if (!empty($search) || !empty($type) || !empty($category) || !empty($year)): ?>
                        <a href="<?= url('search.php') ?>" class="btn btn-light">
                            <i class="fas fa-times me-2"></i>Filtreleri Temizle
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($contents)): ?>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
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
                                        <a class="page-link" href="<?= url("search.php?" . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                
                                if ($start > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="' . url("search.php?" . http_build_query(array_merge($_GET, ['page' => 1]))) . '">1</a></li>';
                                    if ($start > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $start; $i <= $end; $i++) {
                                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                    echo '<a class="page-link" href="' . url("search.php?" . http_build_query(array_merge($_GET, ['page' => $i]))) . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                
                                if ($end < $total_pages) {
                                    if ($end < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="' . url("search.php?" . http_build_query(array_merge($_GET, ['page' => $total_pages]))) . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= url("search.php?" . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">
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
                            <i class="fas fa-search fa-3x text-muted"></i>
                        </div>
                        <h2 class="h4 mb-3">Sonuç Bulunamadı</h2>
                        <p class="text-muted mb-4">
                            Arama kriterlerinize uygun içerik bulunamadı. Lütfen farklı anahtar kelimeler kullanarak tekrar deneyin.
                        </p>
                        <a href="<?= url('search.php') ?>" class="btn btn-primary">
                            <i class="fas fa-sync me-2"></i>Filtreleri Temizle
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-search fa-3x text-muted"></i>
                    </div>
                    <h2 class="h4 mb-3">Film ve Dizi Ara</h2>
                    <p class="text-muted mb-4">
                        Aradığınız film veya diziyi bulmak için sol taraftaki filtreleri kullanabilirsiniz.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 