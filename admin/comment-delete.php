<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// ID kontrolü
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Yorum ID\'si belirtilmedi.');
    redirect('admin/comments.php');
}

try {
    // Yorumu sil
    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    setFlashMessage('success', 'Yorum başarıyla silindi.');
} catch (PDOException $e) {
    setFlashMessage('error', 'Yorum silinirken bir hata oluştu: ' . $e->getMessage());
}

redirect('admin/comments.php'); 