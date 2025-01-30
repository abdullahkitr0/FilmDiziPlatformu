<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!validateCSRFToken()) {
        $errors[] = "Geçersiz form gönderimi.";
    }
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validasyon
    if (empty($email)) {
        $errors[] = "E-posta adresi boş olamaz.";
    }
    
    if (empty($password)) {
        $errors[] = "Şifre boş olamaz.";
    }
    
    if (empty($errors)) {
        try {
            // Kullanıcıyı bul
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Kullanıcı engelli mi kontrol et
                if (isUserBanned($user['id'])) {
                    // Engelleme detaylarını getir
                    $stmt = $db->prepare("
                        SELECT b.*, u.username as banned_by_user
                        FROM user_bans b
                        LEFT JOIN users u ON b.banned_by = u.id
                        WHERE b.user_id = ? 
                        AND (b.expires_at IS NULL OR b.expires_at > NOW())
                        AND b.removed_at IS NULL
                        ORDER BY b.created_at DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$user['id']]);
                    $ban = $stmt->fetch();
                    
                    $banMessage = "Hesabınız engellenmiştir. Engelleme nedeni: " . $ban['reason'];
                    if ($ban['expires_at']) {
                        $banMessage .= "<br>Engelleme bitiş tarihi: " . formatDate($ban['expires_at']);
                    } else {
                        $banMessage .= "<br>Süresiz engelleme";
                    }
                    
                    setFlashMessage($banMessage, 'danger');
                    logUserActivity('login_attempt_banned', [
                        'email' => $email,
                        'ban_id' => $ban['id']
                    ]);
                } else {
                    // Giriş başarılı
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    // Aktiviteyi kaydet
                    logUserActivity('login_success');
                    
                    // Yönlendirme
                    $redirect = isset($_SESSION['redirect_after_login']) 
                        ? $_SESSION['redirect_after_login'] 
                        : 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    
                    redirect($redirect);
                }
            } else {
                $errors[] = "E-posta adresi veya şifre hatalı.";
                logUserActivity('login_failed', [
                    'email' => $email
                ]);
            }
        } catch (PDOException $e) {
            $errors[] = "Veritabanı hatası: " . $e->getMessage();
        }
    }
    
    foreach ($errors as $error) {
        setFlashMessage($error, 'danger');
    }
}

$page_title = 'Giriş Yap';
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
        .login-page {
            background-color: var(--dark-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        .login-card {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .login-card .card-body {
            padding: 2.5rem;
        }
        .login-title {
            color: var(--light-accent);
            font-weight: 600;
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
        .form-label-description a {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .form-label-description a:hover {
            color: var(--light-accent);
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <a href="<?= url() ?>" class="site-logo">
                        <?= SITE_NAME ?>
                    </a>
                    
                    <div class="card login-card">
                        <div class="card-body">
                            <h2 class="login-title">Hesabınıza Giriş Yapın</h2>
                            
                            <?php if (isset($_SESSION['flash_message'])): ?>
                            <div class="alert alert-<?= $_SESSION['flash_type'] ?> mb-4">
                                <?= $_SESSION['flash_message'] ?>
                                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <form action="" method="post" autocomplete="off">
                                <?= getCSRFToken() ?>
                                
                                <div class="mb-4">
                                    <label class="form-label">E-posta Adresi</label>
                                    <input type="email" name="email" class="form-control" placeholder="ornek@example.com" 
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label d-flex justify-content-between align-items-center">
                                        Şifre
                                        <span class="form-label-description">
                                            <a href="forgot-password.php">Şifremi unuttum</a>
                                        </span>
                                    </label>
                                    <input type="password" name="password" class="form-control" placeholder="Şifreniz" required>
                                </div>
                                
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center text-muted mt-4">
                        Hesabınız yok mu? <a href="register.php">Kayıt olun</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html> 