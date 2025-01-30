<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// ID kontrolü
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Kategori ID\'si belirtilmedi.');
    redirect('admin/categories.php');
}

try {
    $db->beginTransaction();
    
    // Kategoriye ait içeriklerin kategori_id'sini null yap
    $stmt = $db->prepare("UPDATE contents SET category_id = NULL WHERE category_id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Kategoriyi sil
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    $db->commit();
    
    setFlashMessage('success', 'Kategori başarıyla silindi.');
} catch (PDOException $e) {
    $db->rollBack();
    setFlashMessage('error', 'Kategori silinirken bir hata oluştu: ' . $e->getMessage());
}

redirect('admin/categories.php'); 