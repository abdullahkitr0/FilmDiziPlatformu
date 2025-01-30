<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Admin kontrolü
requireAdmin();

// AJAX isteği kontrolü
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit(json_encode(['error' => 'Bu endpoint sadece AJAX istekleri için kullanılabilir.']));
}

// POST verisi kontrolü
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['role_id']) || !is_numeric($input['role_id'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Geçersiz rol ID.']));
}

// CSRF token kontrolü
if (!isset($input['csrf_token']) || !validateCSRFToken($input['csrf_token'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'CSRF token doğrulaması başarısız.']));
}

$role_id = (int)$input['role_id'];

try {
    $db->beginTransaction();
    
    // Rolün kullanıcı sayısını kontrol et
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM user_role_relations 
        WHERE role_id = ?
    ");
    $stmt->execute([$role_id]);
    $user_count = $stmt->fetchColumn();
    
    if ($user_count > 0) {
        throw new Exception('Bu role sahip kullanıcılar olduğu için silinemez.');
    }
    
    // Rol izinlerini sil
    $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);
    
    // Rolü sil
    $stmt = $db->prepare("DELETE FROM user_roles WHERE id = ?");
    $stmt->execute([$role_id]);
    
    $db->commit();
    
    // Başarılı yanıt döndür
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    exit(json_encode(['error' => $e->getMessage()]));
} 