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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz form gönderimi.';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            $error = 'Geçerli bir e-posta adresi girin.';
        } else {
            // Kullanıcıyı kontrol et
            $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Şifre sıfırlama token'ı oluştur
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $db->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user['id'], $token, $expires]);
                
                // E-posta gönder
                $reset_link = full_url("reset-password.php?token=" . $token);
                $to = $email;
                $subject = "Şifre Sıfırlama Talebi";
                $message = "Merhaba {$user['username']},\n\n";
                $message .= "Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:\n";
                $message .= $reset_link . "\n\n";
                $message .= "Bu bağlantı 1 saat süreyle geçerlidir.\n";
                $message .= "Eğer şifre sıfırlama talebinde bulunmadıysanız, bu e-postayı dikkate almayın.\n\n";
                $message .= "Saygılarımızla,\n";
                $message .= SITE_NAME;
                
                $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
                $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                if (mail($to, $subject, $message, $headers)) {
                    $success = 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.';
                } else {
                    $error = 'E-posta gönderilirken bir hata oluştu.';
                }
            } else {
                // Güvenlik için kullanıcı bulunamadığında da başarılı mesajı göster
                $success = 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.';
            }
        }
    }
}

$page_title = 'Şifremi Unuttum';
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
        .forgot-page {
            background-color: var(--dark-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        .forgot-card {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .forgot-card .card-body {
            padding: 2.5rem;
        }
        .forgot-title {
            color: var(--light-accent);
            font-weight: 600;
            text-align: center;
            margin-bottom: 1rem;
        }
        .forgot-description {
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
    <div class="forgot-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <a href="<?= url() ?>" class="site-logo">
                        <?= SITE_NAME ?>
                    </a>
                    
                    <div class="card forgot-card">
                        <div class="card-body">
                            <h2 class="forgot-title">Şifrenizi mi Unuttunuz?</h2>
                            <p class="forgot-description">
                                E-posta adresinizi girin, size şifre sıfırlama bağlantısı gönderelim.
                            </p>
                            
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
                                
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Sıfırlama Bağlantısı Gönder
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