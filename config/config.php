<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Site URL'i
define('BASE_URL', '');

// Dosya yolları
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('POSTER_DIR', UPLOAD_DIR . '/posters');

// Site ayarları
define('SITE_NAME', 'Film ve Dizi Öneri Sistemi');
define('SITE_DESCRIPTION', 'Film ve dizi önerileri, incelemeler ve daha fazlası');

// Sayfalama ayarları
define('PER_PAGE', 12);

// OMDb API Ayarları
define('OMDB_API_KEY', ''); // OMDb API anahtarınızı buraya ekleyin

/**
 * URL oluşturma yardımcı fonksiyonu
 * @param string $path
 * @return string
 */
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Tam URL oluşturma yardımcı fonksiyonu
 * @param string $path
 * @return string
 */
function full_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . url($path);
}

/**
 * Yönlendirme fonksiyonu
 */
function redirect($path = '') {
    header('Location: ' . url($path));
    exit;
}

/**
 * Hata mesajı gösterme fonksiyonu
 */
function show_error($message) {
    include ROOT_PATH . '/includes/header.php';
    echo '<div class="container mt-4"><div class="alert alert-danger">' . $message . '</div></div>';
    include ROOT_PATH . '/includes/footer.php';
    exit;
}
?> 