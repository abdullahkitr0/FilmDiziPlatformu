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
    exit('Bu endpoint sadece AJAX istekleri için kullanılabilir.');
}

// Role ID kontrolü
if (!isset($_GET['role_id']) || !is_numeric($_GET['role_id'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Geçersiz rol ID.']));
}

$role_id = (int)$_GET['role_id'];

try {
    // Rol izinlerini getir
    $stmt = $db->prepare("
        SELECT permission_name 
        FROM role_permissions 
        WHERE role_id = ?
    ");
    $stmt->execute([$role_id]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // JSON olarak döndür
    header('Content-Type: application/json');
    echo json_encode($permissions);
} catch (PDOException $e) {
    http_response_code(500);
    exit(json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]));
} 