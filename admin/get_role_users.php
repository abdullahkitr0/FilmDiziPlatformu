<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Role ID kontrolü
$role_id = filter_var($_GET['role_id'] ?? 0, FILTER_VALIDATE_INT);
if (!$role_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz rol ID\'si']);
    exit;
}

try {
    // Role atanmış kullanıcıları getir
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, urr.assigned_at
        FROM users u
        JOIN user_role_relations urr ON u.id = urr.user_id
        WHERE urr.role_id = ?
        ORDER BY urr.assigned_at DESC
    ");
    $stmt->execute([$role_id]);
    $users = $stmt->fetchAll();
    
    // Tarihleri formatla
    foreach ($users as &$user) {
        $user['assigned_at'] = formatDate($user['assigned_at']);
    }
    
    header('Content-Type: application/json');
    echo json_encode($users);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
} 