<?php
require_once '../config/database.php';

// Admin kullanıcı bilgileri
$username = 'test';
$email = 'test@test.com';
$password = 'test1234';
$is_admin = 1;

// Şifreyi hashle
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Önce varsa eski admin kullanıcısını sil
    $stmt = $db->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    // Yeni admin kullanıcısını oluştur
    $stmt = $db->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashed_password, $is_admin]);
    
    echo "Admin kullanıcısı başarıyla oluşturuldu!<br>";
    echo "Kullanıcı adı: " . $username . "<br>";
    echo "Şifre: " . $password . "<br>";
    echo "<a href='../login.php'>Giriş yap</a>";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
} 