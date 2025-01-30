<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Kullanıcı ID'si varsa kullanıcıyı getir
$user = null;
if (isset($_GET['id'])) {
    $stmt = $db->prepare("
        SELECT u.*, GROUP_CONCAT(r.id) as role_ids
        FROM users u
        LEFT JOIN user_role_relations urr ON u.id = urr.user_id
        LEFT JOIN user_roles r ON urr.role_id = r.id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        setFlashMessage('error', 'Kullanıcı bulunamadı.');
        redirect('admin/users.php');
    }
}

// Rolleri getir
$roles = $db->query("
    SELECT * FROM user_roles 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Sayfa başlığı
$page_title = $user ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı Ekle';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('error', 'CSRF token doğrulaması başarısız.');
        redirect('admin/users.php');
    }
    
    // Form verilerini al
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
    
    // Validasyon
    $errors = [];
    if (empty($username)) {
        $errors[] = 'Kullanıcı adı zorunludur.';
    }
    if (empty($email)) {
        $errors[] = 'E-posta adresi zorunludur.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçersiz e-posta adresi.';
    }
    if (!$user && empty($password)) {
        $errors[] = 'Şifre zorunludur.';
    }
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    }
    
    // Hata yoksa kaydet
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            if ($user) {
                // Güncelle
                if (!empty($password)) {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, password = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $username, 
                        $email, 
                        password_hash($password, PASSWORD_DEFAULT),
                        $user['id']
                    ]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $user['id']]);
                }
                
                // Rol ilişkilerini sil
                $db->prepare("DELETE FROM user_role_relations WHERE user_id = ?")->execute([$user['id']]);
                
                // Rol ilişkilerini ekle
                if (!empty($roles)) {
                    $stmt = $db->prepare("
                        INSERT INTO user_role_relations (user_id, role_id) 
                        VALUES (?, ?)
                    ");
                    foreach ($roles as $role_id) {
                        $stmt->execute([$user['id'], $role_id]);
                    }
                }
                
                setFlashMessage('success', 'Kullanıcı başarıyla güncellendi.');
            } else {
                // Ekle
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $username, 
                    $email, 
                    password_hash($password, PASSWORD_DEFAULT)
                ]);
                
                $user_id = $db->lastInsertId();
                
                // Rol ilişkilerini ekle
                if (!empty($roles)) {
                    $stmt = $db->prepare("
                        INSERT INTO user_role_relations (user_id, role_id) 
                        VALUES (?, ?)
                    ");
                    foreach ($roles as $role_id) {
                        $stmt->execute([$user_id, $role_id]);
                    }
                }
                
                setFlashMessage('success', 'Kullanıcı başarıyla eklendi.');
            }
            
            $db->commit();
            redirect('admin/users.php');
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
                                        <label class="form-label required">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" name="username" 
                                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ($user ? htmlspecialchars($user['username']) : '') ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">E-posta Adresi</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ($user ? htmlspecialchars($user['email']) : '') ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label <?= $user ? '' : 'required' ?>">
                                            Şifre
                                            <?php if ($user): ?>
                                                <span class="form-label-description">
                                                    Değiştirmek istemiyorsanız boş bırakın
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                        <input type="password" class="form-control" name="password" 
                                               <?= $user ? '' : 'required' ?>>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Roller</label>
                                        <select class="form-select" name="roles[]" multiple>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= $role['id'] ?>" <?= (isset($_POST['roles']) ? in_array($role['id'], $_POST['roles']) : ($user && in_array($role['id'], explode(',', $user['role_ids'])))) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($role['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <a href="<?= url('admin/users.php') ?>" class="btn btn-link">İptal</a>
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
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html> 