<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// İçe aktarma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    try {
        // CSRF kontrolü
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('CSRF token doğrulaması başarısız.');
        }
        
        $file = $_FILES['import_file'];
        $allowed_types = ['text/csv', 'application/vnd.ms-excel'];
        
        // Validasyon
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Sadece CSV dosyası yükleyebilirsiniz.');
        }
        
        // Dosyayı kaydet
        $upload_path = '../uploads/imports/';
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        $filename = uniqid() . '.csv';
        move_uploaded_file($file['tmp_name'], $upload_path . $filename);
        
        // Log kaydı oluştur
        $stmt = $db->prepare("
            INSERT INTO bulk_content_logs (user_id, operation_type, status, file_path)
            VALUES (?, 'import', 'pending', ?)
        ");
        
        $stmt->execute([$_SESSION['user_id'], 'uploads/imports/' . $filename]);
        $log_id = $db->lastInsertId();
        
        // CSV'yi işle
        $handle = fopen($upload_path . $filename, 'r');
        $header = fgetcsv($handle);
        
        // Başlıkların kontrolü
        $expected_headers = ['title', 'type', 'description', 'poster_url', 'trailer_url', 'release_date', 'imdb_rating', 'meta_title', 'meta_description', 'meta_keywords', 'categories'];
        if ($header !== $expected_headers) {
            throw new Exception('CSV dosyasının başlıkları beklenen formatta değil.');
        }
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                $db->beginTransaction();
                
                $row = array_combine($header, $data);
                
                // İçeriği ekle
                $stmt = $db->prepare("
                    INSERT INTO contents (
                        title, type, description, poster_url, trailer_url,
                        release_date, imdb_rating, meta_title,
                        meta_description, meta_keywords, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $row['title'],
                    $row['type'],
                    $row['description'],
                    $row['poster_url'],
                    $row['trailer_url'],
                  //  $row['release_date'],
                    $row['imdb_rating'],
                    $row['meta_title'] ?? $row['title'],
                    $row['meta_description'] ?? substr($row['description'], 0, 160),
                    $row['meta_keywords'] ?? ''
                ]);
                
                $content_id = $db->lastInsertId();
                
                // Kategori ilişkisini ekle
                if (!empty($row['categories'])) {
                    $categories = explode(',', $row['categories']);
                    foreach ($categories as $category_name) {
                        $category_name = trim($category_name);
                        if (empty($category_name)) continue;
                        
                        // Kategori var mı kontrol et, yoksa ekle
                        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
                        $stmt->execute([$category_name]);
                        $category_id = $stmt->fetchColumn();
                        
                        if (!$category_id) {
                            $stmt = $db->prepare("INSERT INTO categories (name, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                            $stmt->execute([$category_name, createSlug($category_name)]);
                            $category_id = $db->lastInsertId();
                        }
                        
                        // İçerik-kategori ilişkisini ekle
                        $stmt = $db->prepare("INSERT INTO content_categories (content_id, category_id) VALUES (?, ?)");
                        $stmt->execute([$content_id, $category_id]);
                    }
                }
                
                $db->commit();
                $success_count++;
            } catch (Exception $e) {
                $db->rollBack();
                $error_count++;
                $errors[] = "Satır " . ($success_count + $error_count) . ": " . $e->getMessage();
            }
        }
        
        fclose($handle);
        
        // Log kaydını güncelle
        $stmt = $db->prepare("
            UPDATE bulk_content_logs 
            SET status = ?, affected_rows = ?, error_log = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $error_count > 0 ? 'partial' : 'completed',
            $success_count,
            $errors ? implode("\n", $errors) : null,
            $log_id
        ]);
        
        setFlashMessage("İçe aktarma tamamlandı. Başarılı: $success_count, Hata: $error_count", $error_count > 0 ? 'warning' : 'success');
    } catch (Exception $e) {
        setFlashMessage('error', 'Bir hata oluştu: ' . $e->getMessage());
    }
    
    redirect('admin/bulk-content.php');
}

// Dışa aktarma işlemi
if (isset($_GET['export'])) {
    try {
        // Log kaydı oluştur
        $stmt = $db->prepare("
            INSERT INTO bulk_content_logs (user_id, operation_type, status)
            VALUES (?, 'export', 'processing')
        ");
        
        $stmt->execute([$_SESSION['user_id']]);
        $log_id = $db->lastInsertId();
        
        // İçerikleri getir
        $contents = $db->query("
            SELECT c.*, 
                   GROUP_CONCAT(cat.name) as categories
            FROM contents c
            LEFT JOIN content_categories cc ON c.id = cc.content_id
            LEFT JOIN categories cat ON cc.category_id = cat.id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Geçici dosya oluştur
        $temp_file = tempnam(sys_get_temp_dir(), 'csv_');
        $output = fopen($temp_file, 'w');
        
        // Başlıkları yaz
        fputcsv($output, [
            'ID', 'Başlık', 'Tür', 'Açıklama', 'Poster URL', 'Fragman URL',
            'Yayın Tarihi', 'IMDB Puanı', 'Kategoriler', 'Meta Başlık',
            'Meta Açıklama', 'Meta Anahtar Kelimeler', 'Oluşturulma Tarihi'
        ]);
        
        // İçerikleri yaz
        foreach ($contents as $content) {
            fputcsv($output, [
                $content['id'],
                $content['title'],
                $content['type'],
                $content['description'],
                $content['poster_url'],
                $content['trailer_url'],
               // $content['release_date'],
                $content['imdb_rating'],
                $content['categories'],
                $content['meta_title'],
                $content['meta_description'],
                $content['meta_keywords'],
                $content['created_at']
            ]);
        }
        
        fclose($output);
        
        // Dosyayı tarayıcıya gönder
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="contents_' . date('Y-m-d_H-i-s') . '.csv"');
        readfile($temp_file);
        
        // Geçici dosyayı sil
        unlink($temp_file);
        
        // Log kaydını güncelle
        $stmt = $db->prepare("
            UPDATE bulk_content_logs 
            SET status = 'completed', affected_rows = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([count($contents), $log_id]);
        exit;
    } catch (Exception $e) {
        // Log kaydını güncelle
        $stmt = $db->prepare("
            UPDATE bulk_content_logs 
            SET status = 'failed', error_log = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$e->getMessage(), $log_id]);
        
        setFlashMessage('error', 'Bir hata oluştu: ' . $e->getMessage());
        redirect('admin/bulk-content.php');
    }
}

// Dışa aktarma işlemi
if (isset($_GET['export_txt'])) {
    try {
        // İçerikleri getir
        $contents = $db->query("
            SELECT * FROM contents
            ORDER BY created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // TXT dosyası oluştur
        $filename = 'contents_' . date('Y-m-d_H-i-s') . '.txt';
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // İçerikleri yaz
        foreach ($contents as $content) {
            echo "Başlık: " . $content['title'] . "\n";
            echo "Tür: " . $content['type'] . "\n";
            echo "Açıklama: " . $content['description'] . "\n";
           // echo "Yayın Tarihi: " . $content['release_date'] . "\n";
            echo "IMDB Puanı: " . $content['imdb_rating'] . "\n";
            echo "-----------------------------------\n";
        }
        
        exit;
    } catch (Exception $e) {
        setFlashMessage('error', 'Bir hata oluştu: ' . $e->getMessage());
    }
}

// İşlem loglarını getir
$logs = $db->query("
    SELECT l.*, u.username
    FROM bulk_content_logs l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Toplu İçerik İşlemleri';
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
                        <div class="col-auto ms-auto">
                            <div class="btn-list">
                                <a href="?export=1" class="btn btn-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                        <polyline points="7 11 12 16 17 11" />
                                        <line x1="12" y1="4" x2="12" y2="16" />
                                    </svg>
                                    Dışa Aktar (CSV)
                                </a>
                                <a href="?export_txt=1" class="btn btn-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                        <polyline points="7 11 12 16 17 11" />
                                        <line x1="12" y1="4" x2="12" y2="16" />
                                    </svg>
                                    Dışa Aktar (TXT)
                                </a>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                        <polyline points="7 9 12 4 17 9" />
                                        <line x1="12" y1="4" x2="12" y2="16" />
                                    </svg>
                                    İçe Aktar (CSV)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Son İşlemler</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>İşlem</th>
                                        <th>Kullanıcı</th>
                                        <th>Durum</th>
                                        <th>Etkilenen Satır</th>
                                        <th>Tarih</th>
                                        <th>Detay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?php if ($log['operation_type'] === 'import'): ?>
                                            <span class="badge bg-blue">İçe Aktarma</span>
                                            <?php else: ?>
                                            <span class="badge bg-green">Dışa Aktarma</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($log['username']) ?></td>
                                        <td>
                                            <?php if ($log['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Tamamlandı</span>
                                            <?php elseif ($log['status'] === 'failed'): ?>
                                            <span class="badge bg-danger">Başarısız</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">İşleniyor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $log['affected_rows'] ?></td>
                                        <td><?= formatDate($log['created_at']) ?></td>
                                        <td>
                                            <?php if ($log['error_log']): ?>
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="showErrors(<?= htmlspecialchars(json_encode($log['error_log'])) ?>)">
                                                Hataları Göster
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
    
    <!-- İçe Aktarma Modalı -->
    <div class="modal modal-blur fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form action="" method="post" enctype="multipart/form-data">
                    <?= getCSRFToken() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">İçerik İçe Aktar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">CSV Dosyası</label>
                            <input type="file" class="form-control" name="import_file" accept=".csv" required>
                            <small class="form-hint">
                                CSV dosyası şu sütunları içermelidir: title, type, description, poster_url, trailer_url,
                                release_date, imdb_rating, category_id, meta_title, meta_description, meta_keywords
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                            İptal
                        </button>
                        <button type="submit" class="btn btn-primary ms-auto">
                            İçe Aktar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Hata Modalı -->
    <div class="modal modal-blur fade" id="errorModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hata Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="errorLog" class="text-danger"></pre>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İçe Aktarma Formu -->
    <form action="" method="post" enctype="multipart/form-data">
        <label for="import_file">TXT Dosyası Yükle:</label>
        <input type="file" name="import_file" accept=".txt" required>
        <button type="submit">İçe Aktar</button>
    </form>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
    function showErrors(errors) {
        document.getElementById('errorLog').textContent = errors;
        const modal = new bootstrap.Modal(document.getElementById('errorModal'));
        modal.show();
    }
    </script>
</body>
</html> 