<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// İçerik ID'si varsa içeriği ve kategorilerini getir
$content = null;
$selected_categories = [];
if (isset($_GET['id'])) {
    $stmt = $db->prepare("
        SELECT c.*, GROUP_CONCAT(cc.category_id) as category_ids
        FROM contents c
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$_GET['id']]);
    $content = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$content) {
        setFlashMessage('İçerik bulunamadı.', 'danger');
        header('Location: contents.php');
        exit;
    }
    
    // Seçili kategorileri diziye çevir
    if ($content['category_ids']) {
        $selected_categories = explode(',', $content['category_ids']);
    }
}

// Kategorileri getir
$categories = $db->query("
    SELECT * FROM categories 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Platformları getir
$platforms = $db->query("
    SELECT * FROM platforms 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Sayfa başlığı
$page_title = $content ? 'İçerik Düzenle' : 'Yeni İçerik Ekle';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('CSRF token doğrulaması başarısız.', 'danger');
        header('Location: contents.php');
        exit;
    }
    
    // Form verilerini al
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $poster_url = trim($_POST['poster_url']);
    $trailer_url = trim($_POST['trailer_url']);
    $release_date = $_POST['release_date'];
    $imdb_rating = $_POST['imdb_rating'];
    $platforms = isset($_POST['platforms']) ? $_POST['platforms'] : [];
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);
    $meta_keywords = trim($_POST['meta_keywords']);
    $seo_url = trim($_POST['seo_url']);
    
    // Validasyon
    $errors = [];
    if (empty($title)) {
        $errors[] = 'Başlık alanı zorunludur.';
    }
    if (empty($description)) {
        $errors[] = 'Açıklama alanı zorunludur.';
    }
    if (empty($categories)) {
        $errors[] = 'En az bir kategori seçmelisiniz.';
    }
    if (empty($poster_url)) {
        $errors[] = 'Poster URL alanı zorunludur.';
    }
    if (!filter_var($poster_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Geçersiz poster URL\'si.';
    }
    if (!empty($trailer_url) && !filter_var($trailer_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Geçersiz fragman URL\'si.';
    }
    if (empty($release_date)) {
        $errors[] = 'Yayın tarihi alanı zorunludur.';
    }
    if (!is_numeric($imdb_rating) || $imdb_rating < 0 || $imdb_rating > 10) {
        $errors[] = 'IMDB puanı 0-10 arasında olmalıdır.';
    }
    
    // Hata yoksa kaydet
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            if ($content) {
                // Güncelle
                $stmt = $db->prepare("
                    UPDATE contents 
                    SET title = ?, description = ?, type = ?, 
                        poster_url = ?, trailer_url = ?, release_date = ?, 
                        imdb_rating = ?, meta_title = ?, meta_description = ?,
                        meta_keywords = ?, seo_url = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $title, $description, $type,
                    $poster_url, $trailer_url, $release_date, 
                    $imdb_rating, $meta_title, $meta_description,
                    $meta_keywords, $seo_url, $content['id']
                ]);
                
                // Kategori ilişkilerini sil
                $db->prepare("DELETE FROM content_categories WHERE content_id = ?")->execute([$content['id']]);
                
                // Kategori ilişkilerini ekle
                if (!empty($categories)) {
                    $stmt = $db->prepare("
                        INSERT INTO content_categories (content_id, category_id) 
                        VALUES (?, ?)
                    ");
                    foreach ($categories as $category_id) {
                        $stmt->execute([$content['id'], $category_id]);
                    }
                }
                
                // Platform ilişkilerini sil
                $db->prepare("DELETE FROM content_platform_relations WHERE content_id = ?")->execute([$content['id']]);
                
                // Platform ilişkilerini ekle
                if (!empty($platforms)) {
                    $stmt = $db->prepare("
                        INSERT INTO content_platform_relations (content_id, platform_id) 
                        VALUES (?, ?)
                    ");
                    foreach ($platforms as $platform_id) {
                        $stmt->execute([$content['id'], $platform_id]);
                    }
                }
                
                setFlashMessage('İçerik başarıyla güncellendi.', 'success');
            } else {
                // Ekle
                $stmt = $db->prepare("
                    INSERT INTO contents (
                        title, description, type,
                        poster_url, trailer_url, release_date, imdb_rating,
                        meta_title, meta_description, meta_keywords, seo_url,
                        created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        NOW(), NOW()
                    )
                ");
                $stmt->execute([
                    $title, $description, $type,
                    $poster_url, $trailer_url, $release_date, 
                    $imdb_rating, $meta_title, $meta_description,
                    $meta_keywords, $seo_url
                ]);
                
                $content_id = $db->lastInsertId();
                
                // Kategori ilişkilerini ekle
                if (!empty($categories)) {
                    $stmt = $db->prepare("
                        INSERT INTO content_categories (content_id, category_id) 
                        VALUES (?, ?)
                    ");
                    foreach ($categories as $category_id) {
                        $stmt->execute([$content_id, $category_id]);
                    }
                }
                
                // Platform ilişkilerini ekle
                if (!empty($platforms)) {
                    $stmt = $db->prepare("
                        INSERT INTO content_platform_relations (content_id, platform_id) 
                        VALUES (?, ?)
                    ");
                    foreach ($platforms as $platform_id) {
                        $stmt->execute([$content_id, $platform_id]);
                    }
                }
                
                setFlashMessage('İçerik başarıyla eklendi.', 'success');
            }
            
            $db->commit();
            header('Location: contents.php');
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
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
                        <?php if ($content): ?>
                        <div class="col-auto ms-auto">
                            <div class="btn-list">
                                <a href="content-preview.php?id=<?= $content['id'] ?>" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <circle cx="12" cy="12" r="2" />
                                        <path d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7" />
                                    </svg>
                                    Önizle
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="" method="post" class="card">
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
                                        <label class="form-label required">Başlık</label>
                                        <input type="text" class="form-control" name="title" 
                                               value="<?= htmlspecialchars($_POST['title'] ?? ($content['title'] ?? 'Bilgi yok')) ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Açıklama</label>
                                        <textarea class="form-control" name="description" rows="5" required><?= htmlspecialchars($_POST['description'] ?? ($content['description'] ?? 'Bilgi yok')) ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label required">Tür</label>
                                                <select class="form-select" name="type" required>
                                                    <option value="movie" <?= ($_POST['type'] ?? $content['type']) === 'movie' ? 'selected' : '' ?>>Film</option>
                                                    <option value="series" <?= ($_POST['type'] ?? $content['type']) === 'series' ? 'selected' : '' ?>>Dizi</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label required">Kategoriler</label>
                                                <select class="form-select" name="categories[]" multiple required>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?= $category['id'] ?>" 
                                                            <?= in_array($category['id'], $_POST['categories'] ?? $selected_categories) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($category['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label">Platformlar</label>
                                                <select class="form-select" name="platforms[]" multiple>
                                                    <?php foreach ($platforms as $platform): ?>
                                                        <option value="<?= $platform['id'] ?>" <?= (in_array($platform['id'], $_POST['platforms'] ?? []) || ($content && in_array($platform['id'], array_column($content_platforms, 'platform_id')))) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($platform['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label required">Poster URL</label>
                                                <input type="url" class="form-control" name="poster_url" 
                                                       value="<?= htmlspecialchars($_POST['poster_url'] ?? ($content['poster_url'] ?? 'Bilgi yok')) ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Fragman URL</label>
                                                <input type="url" class="form-control" name="trailer_url" 
                                                       value="<?= htmlspecialchars($_POST['trailer_url'] ?? ($content['trailer_url'] ?? 'Bilgi yok')) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label required">Yayın Tarihi</label>
                                                <input type="date" class="form-control" name="release_date" 
                                                       value="<?= htmlspecialchars($_POST['release_date'] ?? ($content['release_date'] ?? 'Bilgi yok')) ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label required">IMDB Puanı</label>
                                                <input type="number" class="form-control" name="imdb_rating" 
                                                       value="<?= htmlspecialchars($_POST['imdb_rating'] ?? ($content['imdb_rating'] ?? 'Bilgi yok')) ?>" 
                                                       min="0" max="10" step="0.1" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- SEO Ayarları -->
                                    <div class="card mt-3">
                                        <div class="card-header">
                                            <h3 class="card-title">SEO Ayarları</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Meta Başlık</label>
                                                        <input type="text" class="form-control" name="meta_title" 
                                                               value="<?= htmlspecialchars($_POST['meta_title'] ?? ($content['meta_title'] ?? 'Bilgi yok')) ?>">
                                                        <small class="form-hint">Boş bırakılırsa içerik başlığı kullanılır.</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">SEO URL</label>
                                                        <input type="text" class="form-control" name="seo_url" 
                                                               value="<?= htmlspecialchars($_POST['seo_url'] ?? ($content['seo_url'] ?? 'Bilgi yok')) ?>">
                                                        <small class="form-hint">Boş bırakılırsa otomatik oluşturulur.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Meta Açıklama</label>
                                                <textarea class="form-control" name="meta_description" rows="3"><?= htmlspecialchars($_POST['meta_description'] ?? ($content['meta_description'] ?? 'Bilgi yok')) ?></textarea>
                                                <small class="form-hint">Boş bırakılırsa içerik açıklamasından ilk 160 karakter kullanılır.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Meta Anahtar Kelimeler</label>
                                                <input type="text" class="form-control" name="meta_keywords" 
                                                       value="<?= htmlspecialchars($_POST['meta_keywords'] ?? ($content['meta_keywords'] ?? 'Bilgi yok')) ?>">
                                                <small class="form-hint">Virgülle ayırarak yazın.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <a href="<?= url('admin/contents.php') ?>" class="btn btn-link">İptal</a>
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>
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
    // SEO alanlarını otomatik doldur
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.querySelector('input[name="title"]');
        const descriptionInput = document.querySelector('textarea[name="description"]');
        const metaTitleInput = document.querySelector('input[name="meta_title"]');
        const metaDescriptionInput = document.querySelector('textarea[name="meta_description"]');
        const seoUrlInput = document.querySelector('input[name="seo_url"]');
        
        // Başlık değiştiğinde
        titleInput.addEventListener('change', function() {
            // Meta başlık boşsa doldur
            if (!metaTitleInput.value) {
                metaTitleInput.value = titleInput.value;
            }
            
            // SEO URL boşsa doldur
            if (!seoUrlInput.value) {
                seoUrlInput.value = generateSeoUrl(titleInput.value);
            }
        });
        
        // Açıklama değiştiğinde
        descriptionInput.addEventListener('change', function() {
            // Meta açıklama boşsa doldur
            if (!metaDescriptionInput.value) {
                metaDescriptionInput.value = generateMetaDescription(descriptionInput.value);
            }
        });
    });

    // SEO URL oluştur
    function generateSeoUrl(title) {
        const tr = {'ş':'s','Ş':'s','ı':'i','İ':'i','ğ':'g','Ğ':'g','ü':'u','Ü':'u','ö':'o','Ö':'o','Ç':'c','ç':'c'};
        return title
            .toLowerCase()
            .replace(/[şŞıİğĞüÜöÖçÇ]/g, letter => tr[letter])
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // Meta açıklama oluştur
    function generateMetaDescription(description, length = 160) {
        description = description.replace(/<[^>]*>/g, '');
        if (description.length > length) {
            description = description.substring(0, length - 3) + '...';
        }
        return description;
    }
    </script>
</body>
</html> 