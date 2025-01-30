<?php
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    redirect();
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('login.php');
}

// Token'ı kontrol et
$stmt = $db->prepare("
    SELECT pr.*, u.username 
    FROM password_resets pr
    JOIN users u ON pr.user_id = u.id
    WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = 'Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz form gönderimi.';
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($password)) {
            $error = 'Lütfen yeni şifrenizi girin.';
        } elseif ($password !== $password_confirm) {
            $error = 'Şifreler eşleşmiyor.';
        } elseif (strlen($password) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır.';
        } else {
            try {
                // Şifreyi güncelle
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([hashPassword($password), $reset['user_id']]);
                
                // Token'ı kullanıldı olarak işaretle
                $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $stmt->execute([$reset['id']]);
                
                setFlashMessage('Şifreniz başarıyla güncellendi. Şimdi giriş yapabilirsiniz.', 'success');
                redirect('login.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Şifre güncellenirken bir hata oluştu.';
            }
        }
    }
}

$page_title = 'Şifre Sıfırlama';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tabler.io CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <style>
        .reset-page {
            background-color: var(--dark-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        .reset-card {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .reset-card .card-body {
            padding: 2.5rem;
        }
        .reset-title {
            color: var(--light-accent);
            font-weight: 600;
            text-align: center;
            margin-bottom: 1rem;
        }
        .reset-description {
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        .form-control {
            background-color: var(--dark-bg);
            border-color: var(--border-color);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        .form-control:focus {
            background-color: var(--dark-bg);
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px rgba(74, 78, 105, 0.25);
        }
        .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--light-accent);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-1px);
        }
        .text-muted {
            color: var(--text-secondary) !important;
        }
        .text-muted a {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .text-muted a:hover {
            color: var(--light-accent);
        }
        .site-logo {
            color: var(--light-accent);
            font-size: 2rem;
            font-weight: 700;
            text-decoration: none;
            margin-bottom: 2rem;
            display: block;
            text-align: center;
        }
        .site-logo:hover {
            color: var(--light-accent);
        }
    </style>
</head>
<body>
    <div class="reset-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <a href="<?= url() ?>" class="site-logo">
                        <?= SITE_NAME ?>
                    </a>
                    
                    <div class="card reset-card">
                        <div class="card-body">
                            <h2 class="reset-title">Şifrenizi Sıfırlayın</h2>
                            <p class="reset-description">
                                Lütfen yeni şifrenizi belirleyin.
                            </p>
                            
                            <?php if (isset($_SESSION['flash_message'])): ?>
                            <div class="alert alert-<?= $_SESSION['flash_type'] ?> mb-4">
                                <?= $_SESSION['flash_message'] ?>
                                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <form action="" method="post" autocomplete="off">
                                <?= getCSRFToken() ?>
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label">Yeni Şifre</label>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Yeni şifrenizi girin" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Şifre Tekrar</label>
                                    <input type="password" name="password_confirm" class="form-control" 
                                           placeholder="Yeni şifrenizi tekrar girin" required>
                                </div>
                                
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-key me-2"></i>Şifreyi Değiştir
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center text-muted mt-4">
                        <a href="login.php"><i class="fas fa-arrow-left me-2"></i>Giriş sayfasına dön</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html> 