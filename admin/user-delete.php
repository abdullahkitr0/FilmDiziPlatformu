<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// ID kontrolü
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Kullanıcı ID\'si belirtilmedi.');
    redirect('admin/users.php');
}

try {
    $db->beginTransaction();
    
    // Kullanıcının yorumlarını sil
    $stmt = $db->prepare("DELETE FROM comments WHERE user_id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Kullanıcının izleme listesini sil
    $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Kullanıcının rol ilişkilerini sil
    $stmt = $db->prepare("DELETE FROM user_role_relations WHERE user_id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Kullanıcıyı sil
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    $db->commit();
    
    setFlashMessage('success', 'Kullanıcı başarıyla silindi.');
} catch (PDOException $e) {
    $db->rollBack();
    setFlashMessage('error', 'Kullanıcı silinirken bir hata oluştu: ' . $e->getMessage());
}

redirect('admin/users.php'); 