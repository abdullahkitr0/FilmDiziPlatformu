<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/OmdbApi.php';

// Admin kontrolü
requireAdmin();

$errors = [];
$success = '';

// OMDb API anahtarını config'den al
$omdbApi = new OmdbApi(OMDB_API_KEY);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken()) {
        $errors[] = "Geçersiz form gönderimi.";
    } else {
        try {
            if (isset($_POST['random_content'])) {
                // Rastgele IMDb ID'leri listesi (popüler film ve dizilerden)
                $randomImdbIds = [
                    'tt0944947', // Game of Thrones
                    'tt0903747', // Breaking Bad
                    'tt4574334', // Stranger Things
                    'tt11280740', // Severance
                    'tt7366338', // Chernobyl
                    'tt0475784', // Westworld
                    'tt0108778', // Friends
                    'tt0455275', // Prison Break
                    'tt2442560', // Peaky Blinders
                    'tt0386676', // The Office
                    'tt0111161', // The Shawshank Redemption
                    'tt0068646', // The Godfather
                    'tt0468569', // The Dark Knight
                    'tt0167260', // LOTR: Return of the King
                    'tt0137523', // Fight Club
                    'tt0110912', // Pulp Fiction
                    'tt0109830', // Forrest Gump
                    'tt0816692', // Interstellar
                    'tt0114369', // Se7en
                    'tt0120737'  // LOTR: Fellowship of the Ring
                ];
                
                // Rastgele bir ID seç
                $imdbId = $randomImdbIds[array_rand($randomImdbIds)];
            } else {
                $imdbId = trim($_POST['imdb_id']);
            }
            
            // IMDb ID'si ile içeriği getir
            $content = $omdbApi->getByImdbId($imdbId);
            
            if (!isset($content['Response']) || $content['Response'] !== 'True') {
                throw new Exception('İçerik bulunamadı veya API hatası.');
            }
            
            // Veritabanına kaydet
            $db->beginTransaction();
            
            // Önce bu IMDb ID'si ile içerik var mı kontrol et
            $stmt = $db->prepare("SELECT id FROM contents WHERE imdb_id = ?");
            $stmt->execute([$content['imdbID']]);
            if ($stmt->fetchColumn()) {
                throw new Exception('Bu içerik zaten eklenmiş.');
            }
            
            // Kategori kontrolü/oluşturma
            $genres = explode(', ', $content['Genre']);
            $categoryIds = [];
            
            foreach ($genres as $genre) {
                $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$genre]);
                $categoryId = $stmt->fetchColumn();
                
                if (!$categoryId) {
                    $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                    $stmt->execute([$genre, createSlug($genre)]);
                    $categoryId = $db->lastInsertId();
                }
                
                $categoryIds[] = $categoryId;
            }
            
            // İçeriği ekle
            $stmt = $db->prepare("
                INSERT INTO contents (
                    title, original_title, slug, description, 
                    release_year, imdb_id, imdb_rating, 
                    poster_url, type, runtime, language, country,
                    director, writer, awards, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $content['Title'],
                $content['Title'],
                createSlug($content['Title']),
                $content['Plot'],
                substr($content['Year'], 0, 4),
                $content['imdbID'],
                $content['imdbRating'],
                $content['Poster'],
                $content['Type'] === 'series' ? 'series' : 'movie',
                $content['Runtime'],
                $content['Language'],
                $content['Country'],
                $content['Director'],
                $content['Writer'],
                $content['Awards']
            ]);
            
            $contentId = $db->lastInsertId();
            
            // Kategorileri bağla
            foreach ($categoryIds as $categoryId) {
                $stmt = $db->prepare("
                    INSERT INTO content_categories (content_id, category_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$contentId, $categoryId]);
            }
            
            // Oyuncuları ekle
            $actors = explode(', ', $content['Actors']);
            foreach ($actors as $actor) {
                $stmt = $db->prepare("
                    INSERT INTO content_actors (content_id, actor_name)
                    VALUES (?, ?)
                ");
                $stmt->execute([$contentId, trim($actor)]);
            }
            
            $db->commit();
            $success = "İçerik başarıyla eklendi: " . $content['Title'];
            
            // Admin log
            logAdminActivity('content_added', [
                'content_id' => $contentId,
                'imdb_id' => $imdbId
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Hata: " . $e->getMessage();
        }
    }
}

$page_title = 'OMDb API ile İçerik Ekle';
require_once '../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container-xl">
        <!-- Page title -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        OMDb API ile İçerik Ekle
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">İçerik Ekle</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <?= htmlspecialchars($success) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($errors): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <?= getCSRFToken() ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">IMDb ID</label>
                                            <div class="input-group">
                                                <input type="text" name="imdb_id" class="form-control" 
                                                       placeholder="örn: tt11280740">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>İçerik Ekle
                                                </button>
                                            </div>
                                            <small class="form-hint">
                                                IMDb URL'sinden ID'yi kopyalayabilirsiniz. 
                                                Örnek: https://www.imdb.com/title/tt11280740/ -> tt11280740
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Rastgele İçerik</label>
                                            <button type="submit" name="random_content" value="1" class="btn btn-success w-100">
                                                <i class="fas fa-random me-2"></i>Rastgele Film/Dizi Ekle
                                            </button>
                                            <small class="form-hint">
                                                Popüler film ve dizilerden rastgele bir içerik ekler.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 