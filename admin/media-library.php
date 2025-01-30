<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Dosya yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media'])) {
    try {
        $file = $_FILES['media'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validasyon
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Desteklenmeyen dosya türü.');
        }
        
        if ($file['size'] > $max_size) {
            throw new Exception('Dosya boyutu çok büyük (max: 5MB).');
        }
        
        // Dosya adını oluştur
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $extension;
        $upload_path = '../uploads/media/';
        
        // Klasör yoksa oluştur
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // Dosyayı yükle
        if (move_uploaded_file($file['tmp_name'], $upload_path . $new_filename)) {
            // Veritabanına kaydet
            $stmt = $db->prepare("
                INSERT INTO media_library (file_name, file_path, file_type, file_size, uploaded_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $file['name'],
                'uploads/media/' . $new_filename,
                $file['type'],
                $file['size'],
                $_SESSION['user_id']
            ]);
            
            setFlashMessage('success', 'Dosya başarıyla yüklendi.');
        } else {
            throw new Exception('Dosya yüklenirken bir hata oluştu.');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Bir hata oluştu: ' . $e->getMessage());
    }
    
    redirect('admin/media-library.php');
}

// Dosya silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Dosya bilgilerini al
        $stmt = $db->prepare("SELECT * FROM media_library WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $file = $stmt->fetch();
        
        if ($file) {
            // Fiziksel dosyayı sil
            $file_path = '../' . $file['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Veritabanından sil
            $stmt = $db->prepare("DELETE FROM media_library WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            
            setFlashMessage('success', 'Dosya başarıyla silindi.');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Bir hata oluştu: ' . $e->getMessage());
    }
    
    redirect('admin/media-library.php');
}

// Medya dosyalarını getir
$media = $db->query("
    SELECT m.*, u.username 
    FROM media_library m
    JOIN users u ON m.uploaded_by = u.id
    ORDER BY m.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Medya Kütüphanesi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin Paneli</title>
    <!-- Tabler.io CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
    <!-- Dropzone.js CSS -->

    <link href="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone.css" rel="stylesheet" type="text/css" />
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
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                Dosya Yükle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row row-cards">
                        <?php foreach ($media as $file): ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card">
                                <div class="card-img-top img-responsive img-responsive-16x9" style="background-image: url(<?= str_starts_with($file['file_type'], 'image/') ? '../' . $file['file_path'] : 'https://via.placeholder.com/300x169.png?text=Video' ?>)"></div>
                                <div class="card-body">
                                    <h3 class="card-title"><?= htmlspecialchars($file['file_name']) ?></h3>
                                    <p class="text-muted">
                                        Yükleyen: <?= htmlspecialchars($file['username']) ?><br>
                                        Boyut: <?= formatBytes($file['file_size']) ?><br>
                                        Tarih: <?= formatDate($file['created_at']) ?>
                                    </p>
                                    <div class="d-flex">
                                        <input type="text" class="form-control me-2" value="<?= url($file['file_path']) ?>" readonly>
                                        <a href="?delete=<?= $file['id'] ?>" class="btn btn-danger btn-icon" onclick="return confirm('Bu dosyayı silmek istediğinize emin misiniz?')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <line x1="4" y1="7" x2="20" y2="7" />
                                                <line x1="10" y1="11" x2="10" y2="17" />
                                                <line x1="14" y1="11" x2="14" y2="17" />
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <?php require_once 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Dosya yükleme modalı -->
    <div class="modal modal-blur fade" id="uploadModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dosya Yükle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post" enctype="multipart/form-data" class="dropzone" id="mediaDropzone">
                        <?= getCSRFToken() ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <!-- Dropzone.js -->
    <script src="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone-min.js"></script>
    <script>
    Dropzone.options.mediaDropzone = {
        paramName: "media",
        maxFilesize: 5, // MB
        acceptedFiles: "image/*,video/mp4",
        dictDefaultMessage: "Dosyaları buraya sürükleyin veya tıklayarak seçin",
        dictFileTooBig: "Dosya çok büyük ({{filesize}}MB). Maximum dosya boyutu: {{maxFilesize}}MB.",
        dictInvalidFileType: "Bu dosya türü desteklenmiyor.",
        init: function() {
            this.on("success", function(file, response) {
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            });
        }
    };
    </script>
</body>
</html> 