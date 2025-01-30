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

// Kayıt kapalıysa ana sayfaya yönlendir
if (getSetting('user_registration', '1') != '1') {
    setFlashMessage('Şu anda yeni kayıt alımı kapalıdır.', 'warning');
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!validateCSRFToken()) {
        $errors[] = "Geçersiz form gönderimi.";
    }
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validasyon
    if (empty($username)) {
        $errors[] = "Kullanıcı adı boş olamaz.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Kullanıcı adı en az 3 karakter olmalıdır.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir.";
    }
    
    if (empty($email)) {
        $errors[] = "E-posta adresi boş olamaz.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    }
    
    if (empty($password)) {
        $errors[] = "Şifre boş olamaz.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    }
    
    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Kullanıcı adı ve e-posta kontrolü
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.");
            }
            
            // Kullanıcıyı ekle
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);
            
            $user_id = $db->lastInsertId();
            
            // User rolünü getir
            $stmt = $db->prepare("SELECT id FROM user_roles WHERE name = 'user'");
            $stmt->execute();
            $role_id = $stmt->fetchColumn();
            
            if (!$role_id) {
                throw new Exception("Varsayılan rol bulunamadı.");
            }
            
            // Kullanıcıya rol ata
            if (!assignRole($user_id, $role_id, null)) {
                throw new Exception("Rol atama işlemi başarısız oldu.");
            }
            
            // Aktiviteyi kaydet
            logUserActivity('user_registered', [
                'username' => $username,
                'email' => $email
            ]);
            
            $db->commit();
            
            // Otomatik giriş yap
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = 0;
            
            setFlashMessage('Hesabınız başarıyla oluşturuldu.', 'success');
            redirect('index.php');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = $e->getMessage();
        }
    }
    
    foreach ($errors as $error) {
        setFlashMessage($error, 'danger');
    }
}

$page_title = 'Kayıt Ol';
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
        .register-page {
            background-color: var(--dark-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        .register-card {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .register-card .card-body {
            padding: 2.5rem;
        }
        .register-title {
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
    </style>
</head>
<body>
    <div class="register-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <a href="<?= url() ?>" class="site-logo">
                        <?= SITE_NAME ?>
                    </a>
                    
                    <div class="card register-card">
                        <div class="card-body">
                            <h2 class="register-title">Yeni Hesap Oluşturun</h2>
                            
                            <?php if (isset($_SESSION['flash_message'])): ?>
                            <div class="alert alert-<?= $_SESSION['flash_type'] ?> mb-4">
                                <?= $_SESSION['flash_message'] ?>
                                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <form action="" method="post" autocomplete="off">
                                <?= getCSRFToken() ?>
                                
                                <div class="mb-4">
                                    <label class="form-label">Kullanıcı Adı</label>
                                    <input type="text" name="username" class="form-control" placeholder="kullanici_adi" 
                                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">E-posta Adresi</label>
                                    <input type="email" name="email" class="form-control" placeholder="ornek@example.com" 
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Şifre</label>
                                    <input type="password" name="password" class="form-control" placeholder="Şifreniz" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Şifre Tekrar</label>
                                    <input type="password" name="password_confirm" class="form-control" placeholder="Şifrenizi tekrar girin" required>
                                </div>
                                
                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center text-muted mt-4">
                        Zaten hesabınız var mı? <a href="login.php">Giriş yapın</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html>