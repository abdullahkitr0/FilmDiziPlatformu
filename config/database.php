<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'film_dizi_db');

try {
    // PDO bağlantısını oluştur
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    // Hata durumunda kullanıcıya bilgi ver
    die("Veritabanı bağlantısı başarısız: " . $e->getMessage());
}

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flash mesajları için yardımcı fonksiyonlar
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// XSS koruması için yardımcı fonksiyon
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}



// Sayfalama için yardımcı fonksiyon
function paginate($total, $per_page, $current_page) {
    $total_pages = ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    
    $start = ($current_page - 1) * $per_page;
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'start' => $start,
        'per_page' => $per_page
    ];
}
?> 