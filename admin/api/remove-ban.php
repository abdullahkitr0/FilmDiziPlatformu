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

if (!isset($input['ban_id']) || !is_numeric($input['ban_id'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Geçersiz engelleme ID.']));
}

// CSRF token kontrolü
if (!isset($input['csrf_token']) || !validateCSRFToken($input['csrf_token'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'CSRF token doğrulaması başarısız.']));
}

$ban_id = (int)$input['ban_id'];

try {
    $db->beginTransaction();
    
    // Engellemeyi getir
    $stmt = $db->prepare("
        SELECT b.*, u.username 
        FROM user_bans b
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->execute([$ban_id]);
    $ban = $stmt->fetch();
    
    if (!$ban) {
        throw new Exception('Engelleme bulunamadı.');
    }
    
    // Engellemeyi kaldır
    $stmt = $db->prepare("UPDATE user_bans SET removed_at = NOW(), removed_by = ? WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $ban_id]);
    
    // Kullanıcı aktivitesini kaydet
    logUserActivity('user_unbanned', [
        'ban_id' => $ban_id,
        'user_id' => $ban['user_id'],
        'username' => $ban['username']
    ]);
    
    $db->commit();
    
    // Başarılı yanıt döndür
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    exit(json_encode(['error' => $e->getMessage()]));
} 