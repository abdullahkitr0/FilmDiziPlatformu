<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// ID kontrolü
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Platform ID\'si belirtilmedi.');
    redirect('admin/platforms.php');
}

try {
    $db->beginTransaction();
    
    // Platform ilişkilerini sil
    $stmt = $db->prepare("DELETE FROM content_platform_relations WHERE platform_id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Platformu sil
    $stmt = $db->prepare("DELETE FROM platforms WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    $db->commit();
    
    setFlashMessage('success', 'Platform başarıyla silindi.');
} catch (PDOException $e) {
    $db->rollBack();
    setFlashMessage('error', 'Platform silinirken bir hata oluştu: ' . $e->getMessage());
}

redirect('admin/platforms.php'); 