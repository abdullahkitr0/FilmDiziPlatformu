<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hata mesajlarını JSON yanıtında döndüreceğiz

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// PDO hata modunu ayarla
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Admin kontrolü
    if (!isAdmin()) {
        http_response_code(403);
        throw new Exception('Bu işlem için yetkiniz yok');
    }

    // Role ID kontrolü
    $role_id = filter_var($_GET['role_id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$role_id) {
        http_response_code(400);
        throw new Exception('Geçersiz rol ID\'si');
    }

    // Önce rolün var olduğunu kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_roles WHERE id = ?");
    $stmt->execute([$role_id]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(404);
        throw new Exception('Rol bulunamadı');
    }

    // Role atanmış kullanıcıları getir
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, urr.created_at as assigned_at
        FROM users u
        INNER JOIN user_role_relations urr ON u.id = urr.user_id
        WHERE urr.role_id = ?
        ORDER BY urr.created_at DESC
    ");
    $stmt->execute([$role_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tarihleri formatla ve XSS koruması uygula
    foreach ($users as &$user) {
        $user['assigned_at'] = formatDate($user['assigned_at']);
        $user['username'] = htmlspecialchars($user['username']);
        $user['email'] = htmlspecialchars($user['email']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $users
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log('Veritabanı hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Veritabanı hatası oluştu',
        'details' => DEBUG_MODE ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Genel hata: ' . $e->getMessage());
    // http_response_code zaten try bloğunda ayarlandı
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => DEBUG_MODE ? $e->getTraceAsString() : null
    ], JSON_UNESCAPED_UNICODE);
} 