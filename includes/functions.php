<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Kullanıcının giriş yapmış olup olmadığını kontrol eder
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Kullanıcının admin olup olmadığını kontrol eder
 */
function isAdmin($user_id = null) {
    if ($user_id === null) {
        if (!isLoggedIn()) {
            return false;
        }
        $user_id = $_SESSION['user_id'];
    }
    
    global $db;
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM user_role_relations ur 
        JOIN user_roles r ON ur.role_id = r.id 
        WHERE ur.user_id = ? AND r.name = 'admin'
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Kullanıcının giriş yapmış olmasını gerektirir, yapmamışsa login sayfasına yönlendirir
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Bu sayfayı görüntülemek için giriş yapmalısınız.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Kullanıcının admin olmasını gerektirir, değilse ana sayfaya yönlendirir
 */
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        setFlashMessage('Bu sayfaya erişim yetkiniz yok.', 'danger');
        redirect('login.php');
        exit;
    }
}

/**
 * Güvenli bir şekilde string'i filtreler
 * @param string $str
 * @return string
 */
function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash mesajı oluşturur
 * @param string $message
 * @param string $type success|info|warning|danger
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Şifreyi güvenli bir şekilde hashler
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Şifrenin doğru olup olmadığını kontrol eder
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * URL'yi SEO dostu hale getirir
 * @param string $str
 * @return string
 */
function slugify($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

/**
 * Tarihi Türkçe formata çevirir
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

/**
 * Metni kısaltır
 * @param string $text
 * @param int $length
 * @return string
 */
function truncate($text, $length = 100) {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}

/**
 * CSRF token oluşturur
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token'ı doğrular
 * @param string|null $token
 * @return bool
 */
function validateCSRFToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * CSRF token HTML input alanını oluşturur
 * @return string
 */
function getCSRFToken() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Dosya yükleme işlemini gerçekleştirir
 * @param array $file $_FILES array
 * @param string $uploadDir Yükleme dizini
 * @param array $allowedTypes İzin verilen dosya türleri
 * @return string|false Başarılı ise dosya yolu, değilse false
 */
function uploadFile($file, $uploadDir, $allowedTypes = ['jpg', 'jpeg', 'png']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);

    if (!in_array($extension, $allowedTypes)) {
        return false;
    }

    $newFilename = uniqid() . '.' . $extension;
    $destination = $uploadDir . '/' . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return false;
    }

    return $newFilename;
}

/**
 * Kullanıcının izleme listesinde olup olmadığını kontrol eder
 * @param int $contentId İçerik ID'si
 * @param int|null $userId Kullanıcı ID'si (null ise aktif kullanıcı kullanılır)
 * @return bool
 */
function isInWatchlist($contentId, $userId = null) {
    if ($userId === null) {
        if (!isLoggedIn()) return false;
        $userId = $_SESSION['user_id'];
    }
    
    global $db;
    $stmt = $db->prepare('
        SELECT 1 
        FROM watchlist 
        WHERE user_id = ? AND content_id = ?
    ');
    $stmt->execute([$userId, $contentId]);
    return (bool) $stmt->fetch();
}

/**
 * Kullanıcının içeriğe verdiği puanı getirir
 * @param int $userId
 * @param int $contentId
 * @return int|false
 */
function getUserRating($userId, $contentId) {
    global $db;
    $stmt = $db->prepare("SELECT rating FROM comments WHERE user_id = ? AND content_id = ?");
    $stmt->execute([$userId, $contentId]);
    return $stmt->fetchColumn();
}

/**
 * İçeriğin ortalama puanını hesaplar
 * @param int $contentId
 * @return float
 */
function getAverageRating($contentId) {
    global $db;
    $stmt = $db->prepare("SELECT AVG(rating) FROM comments WHERE content_id = ?");
    $stmt->execute([$contentId]);
    return round($stmt->fetchColumn(), 1);
}

/**
 * Kullanıcı işlemleri için yardımcı fonksiyonlar
 */
function getUserById($id) {
    global $db;
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUserByEmail($email) {
    global $db;
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function createUser($name, $email, $password) {
    global $db;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    return $stmt->execute([$name, $email, $hashedPassword]);
}

/**
 * İçerik işlemleri için yardımcı fonksiyonlar
 */
function getContentById($id) {
    global $db;
    $stmt = $db->prepare('
        SELECT c.*, 
               GROUP_CONCAT(cat.name) as category_names,
               GROUP_CONCAT(cat.id) as category_ids
        FROM contents c 
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        WHERE c.id = ?
        GROUP BY c.id
    ');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getContentPlatforms($content_id) {
    global $db;
    $stmt = $db->prepare('
        SELECT p.* 
        FROM platforms p 
        JOIN content_platforms cp ON p.id = cp.platform_id 
        WHERE cp.content_id = ?
    ');
    $stmt->execute([$content_id]);
    return $stmt->fetchAll();
}

/**
 * Yorum işlemleri için yardımcı fonksiyonlar
 */
function getContentComments($content_id) {
    global $db;
    $stmt = $db->prepare('
        SELECT c.*, u.name as user_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.content_id = ? 
        ORDER BY c.created_at DESC
    ');
    $stmt->execute([$content_id]);
    return $stmt->fetchAll();
}

function addComment($content_id, $comment, $rating) {
    global $db;
    $stmt = $db->prepare('
        INSERT INTO comments (content_id, user_id, comment, rating) 
        VALUES (?, ?, ?, ?)
    ');
    return $stmt->execute([
        $content_id,
        $_SESSION['user_id'],
        $comment,
        $rating
    ]);
}

/**
 * Kategori işlemleri için yardımcı fonksiyonlar
 */
function getAllCategories() {
    global $db;
    $stmt = $db->prepare('SELECT * FROM categories ORDER BY name');
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Metni slug formatına dönüştürür
 */
function createSlug($text) {
    // Türkçe karakterleri değiştir
    $text = str_replace(
        ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
        ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'],
        $text
    );
    
    // Küçük harfe çevir
    $text = mb_strtolower($text);
    
    // Alfanumerik olmayan karakterleri tire ile değiştir
    $text = preg_replace('/[^a-z0-9]/', '-', $text);
    
    // Birden fazla tireyi tek tireye indir
    $text = preg_replace('/-+/', '-', $text);
    
    // Baştaki ve sondaki tireleri kaldır
    return trim($text, '-');
}

/**
 * Kategori ekler veya günceller
 */
function saveCategory($data, $id = null) {
    global $db;
    
    try {
        // Slug oluştur
        $slug = createSlug($data['name']);
        
        // Aynı slug'a sahip başka kategori var mı kontrol et
        $stmt = $db->prepare("SELECT 1 FROM categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id ?? 0]);
        
        if ($stmt->fetchColumn()) {
            // Eğer aynı slug varsa sonuna rastgele bir sayı ekle
            $slug .= '-' . rand(1, 999);
        }
        
        if ($id) {
            // Güncelleme
            $stmt = $db->prepare("
                UPDATE categories 
                SET name = ?, description = ?, slug = ?
                WHERE id = ?
            ");
            $stmt->execute([$data['name'], $data['description'] ?? null, $slug, $id]);
        } else {
            // Yeni ekle
            $stmt = $db->prepare("
                INSERT INTO categories (name, description, slug)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$data['name'], $data['description'] ?? null, $slug]);
            $id = $db->lastInsertId();
        }
        
        return $id;
    } catch (PDOException $e) {
        // Hata durumunda false döndür
        return false;
    }
}

/**
 * Platform işlemleri için yardımcı fonksiyonlar
 */
function getAllPlatforms() {
    global $db;
    $stmt = $db->prepare('SELECT * FROM platforms ORDER BY name');
    $stmt->execute();
    return $stmt->fetchAll();
}

function createPlatform($name, $url) {
    global $db;
    $stmt = $db->prepare('INSERT INTO platforms (name, url) VALUES (?, ?)');
    return $stmt->execute([$name, $url]);
}

/**
 * Yardımcı fonksiyonlar
 */
function formatRating($rating) {
    return number_format($rating, 1);
}

/**
 * Arama ve filtreleme için yardımcı fonksiyonlar
 */
function buildSearchQuery($params) {
    $where = ['1=1'];
    $values = [];
    
    if (!empty($params['q'])) {
        $where[] = '(c.title LIKE ? OR c.description LIKE ?)';
        $values[] = "%{$params['q']}%";
        $values[] = "%{$params['q']}%";
    }
    
    if (!empty($params['type'])) {
        $where[] = 'c.type = ?';
        $values[] = $params['type'];
    }
    
    if (!empty($params['category'])) {
        $where[] = 'cc.category_id = ?';
        $values[] = $params['category'];
    }
    
    if (!empty($params['year'])) {
        $where[] = 'c.release_year = ?';
        $values[] = $params['year'];
    }
    
    $sql = 'WHERE ' . implode(' AND ', $where);
    
    if (!empty($params['sort'])) {
        $sql .= match($params['sort']) {
            'title_asc' => ' ORDER BY c.title ASC',
            'title_desc' => ' ORDER BY c.title DESC',
            'year_asc' => ' ORDER BY c.release_year ASC',
            'year_desc' => ' ORDER BY c.release_year DESC',
            'rating_asc' => ' ORDER BY c.imdb_rating ASC',
            'rating_desc' => ' ORDER BY c.imdb_rating DESC',
            default => ' ORDER BY c.created_at DESC'
        };
    } else {
        $sql .= ' ORDER BY c.created_at DESC';
    }
    
    return [
        'sql' => $sql,
        'values' => $values
    ];
}

/**
 * Kullanıcıya özel içerik önerileri getirir
 * @param int $userId Kullanıcı ID'si
 * @param int $limit Önerilecek içerik sayısı
 * @return array
 */
function getRecommendations($userId, $limit = 10) {
    global $db;
    
    // Kullanıcının en son izlediği/beğendiği içeriklerin kategorilerini al
    $stmt = $db->prepare("
        SELECT DISTINCT cc.category_id
        FROM comments cm
        JOIN contents c ON c.id = cm.content_id
        JOIN content_categories cc ON c.id = cc.content_id
        WHERE cm.user_id = ? AND cm.rating >= 7
        ORDER BY cm.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($categories)) {
        // Eğer kullanıcının beğendiği içerik yoksa, en popüler içerikleri öner
        $stmt = $db->prepare("
            SELECT c.*, 
                   AVG(cm.rating) as avg_rating,
                   COUNT(cm.id) as rating_count,
                   GROUP_CONCAT(cat.name) as categories
            FROM contents c
            LEFT JOIN comments cm ON c.id = cm.content_id
            LEFT JOIN content_categories cc ON c.id = cc.content_id
            LEFT JOIN categories cat ON cc.category_id = cat.id
            WHERE c.id NOT IN (
                SELECT content_id 
                FROM watchlist 
                WHERE user_id = ?
            )
            GROUP BY c.id
            HAVING avg_rating >= 7
            ORDER BY rating_count DESC, avg_rating DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
    } else {
        // Kullanıcının beğendiği kategorilerdeki yüksek puanlı içerikleri öner
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $params = array_merge($categories, [$userId, $limit]);
        
        $stmt = $db->prepare("
            SELECT c.*, 
                   AVG(cm.rating) as avg_rating,
                   COUNT(cm.id) as rating_count,
                   GROUP_CONCAT(cat.name) as categories
            FROM contents c
            LEFT JOIN comments cm ON c.id = cm.content_id
            LEFT JOIN content_categories cc ON c.id = cc.content_id
            LEFT JOIN categories cat ON cc.category_id = cat.id
            WHERE cc.category_id IN ($placeholders)
            AND c.id NOT IN (
                SELECT content_id 
                FROM watchlist 
                WHERE user_id = ?
            )
            GROUP BY c.id
            HAVING avg_rating >= 7
            ORDER BY rating_count DESC, avg_rating DESC
            LIMIT ?
        ");
        $stmt->execute($params);
    }
    
    return $stmt->fetchAll();
}

/**
 * Benzer içerikleri getirir
 * @param int $contentId İçerik ID'si
 * @param int $limit Önerilecek içerik sayısı
 * @return array
 */
function getSimilarContent($contentId, $limit = 6) {
    global $db;
    
    // İçeriğin kategorilerini al
    $stmt = $db->prepare("
        SELECT category_id
        FROM content_categories
        WHERE content_id = ?
    ");
    $stmt->execute([$contentId]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($categories)) return [];
    
    $placeholders = str_repeat('?,', count($categories) - 1) . '?';
    $params = array_merge($categories, [$contentId, $limit]);
    
    $stmt = $db->prepare("
        SELECT c.*, 
               AVG(cm.rating) as avg_rating,
               COUNT(cm.id) as rating_count,
               GROUP_CONCAT(cat.name) as categories
        FROM contents c
        LEFT JOIN comments cm ON c.id = cm.content_id
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        WHERE cc.category_id IN ($placeholders)
        AND c.id != ?
        GROUP BY c.id
        ORDER BY avg_rating DESC, rating_count DESC
        LIMIT ?
    ");
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Ziyaret istatistiğini kaydet
 */
function logVisit($page_url) {
    global $db;
    
    // Kullanıcı ID'si kontrolü
    $user_id = null;
    if (isLoggedIn() && isset($_SESSION['user_id'])) {
        // Kullanıcının varlığını kontrol et
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $user_id = $_SESSION['user_id'];
        }
    }
    
    $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        $stmt = $db->prepare("INSERT INTO visitor_stats (page_url, visitor_ip, user_id, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$page_url, $visitor_ip, $user_id, $user_agent]);
    } catch (PDOException $e) {
        // Hata durumunda sessizce devam et
        error_log("Ziyaret kaydı hatası: " . $e->getMessage());
    }
}

/**
 * İçerik görüntülenmesini kaydet
 */
function logContentView($content_id) {
    global $db;
    
    // Kullanıcı ID'si kontrolü
    $user_id = null;
    if (isLoggedIn() && isset($_SESSION['user_id'])) {
        // Kullanıcının varlığını kontrol et
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $user_id = $_SESSION['user_id'];
        }
    }
    
    $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        // İçeriğin varlığını kontrol et
        $stmt = $db->prepare("SELECT id FROM contents WHERE id = ?");
        $stmt->execute([$content_id]);
        if ($stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO content_views (content_id, user_id, visitor_ip) VALUES (?, ?, ?)");
            $stmt->execute([$content_id, $user_id, $visitor_ip]);
        }
    } catch (PDOException $e) {
        // Hata durumunda sessizce devam et
        error_log("İçerik görüntüleme kaydı hatası: " . $e->getMessage());
    }
}

/**
 * Kullanıcı aktivitesini kaydeder
 * 
 * @param string $activity_type Aktivite türü
 * @param array|null $activity_details Aktivite detayları
 * @return bool
 */
function logUserActivity($activity_type, $activity_details = null) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO user_activities (user_id, activity_type, activity_details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $activity_type,
            $activity_details ? json_encode($activity_details) : null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (PDOException $e) {
        error_log("Aktivite log hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Admin aktivitesini kaydeder
 * 
 * @param string $action Aktivite türü
 * @param array|null $details Aktivite detayları
 * @return bool
 */
function logAdminActivity($action, $details = null) {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO admin_logs (user_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Kullanıcının rollerini getirir
 * 
 * @param int $user_id Kullanıcı ID
 * @return array
 */
function getUserRoles($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT r.* 
            FROM user_roles r
            JOIN user_role_relations urr ON r.id = urr.role_id
            WHERE urr.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Kullanıcının belirli bir role sahip olup olmadığını kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @param string $role_name Rol adı
 * @return bool
 */
function hasRole($user_id, $role_name) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM user_role_relations urr
            JOIN user_roles r ON urr.role_id = r.id
            WHERE urr.user_id = ? AND r.name = ?
        ");
        $stmt->execute([$user_id, $role_name]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Kullanıcının belirli bir izne sahip olup olmadığını kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @param string $permission İzin adı
 * @return bool
 */
function hasPermission($user_id, $permission) {
    global $db;
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM user_role_relations ur 
        JOIN role_permissions rp ON ur.role_id = rp.role_id 
        WHERE ur.user_id = ? AND rp.permission_name = ?
    ");
    $stmt->execute([$user_id, $permission]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Kullanıcının engellenip engellenmediğini kontrol eder
 * 
 * @param int $user_id Kullanıcı ID
 * @return bool
 */
function isUserBanned($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM user_bans 
            WHERE user_id = ? 
            AND (expires_at IS NULL OR expires_at > NOW())
            AND removed_at IS NULL
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Site ayarını getirir
 * 
 * @param string $key Ayar anahtarı
 * @param mixed $default Varsayılan değer
 * @return mixed
 */
function getSetting($key, $default = null) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT setting_value 
            FROM site_settings 
            WHERE setting_key = ?
        ");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        
        return $value !== false ? $value : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Site ayarını günceller
 * 
 * @param string $key Ayar anahtarı
 * @param mixed $value Yeni değer
 * @return bool
 */
function updateSetting($key, $value) {
    global $db;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("
            UPDATE site_settings 
            SET setting_value = ?, updated_by = ?, updated_at = NOW()
            WHERE setting_key = ?
        ");
        
        return $stmt->execute([$value, $_SESSION['user_id'], $key]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Kullanıcıyı engelle
 */
function banUser($user_id, $reason, $until = null) {
    global $db;
    if (!isAdmin()) return false;
    
    $stmt = $db->prepare("INSERT INTO user_bans (user_id, banned_by, reason, banned_until) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $_SESSION['user_id'], $reason, $until]);
}

/**
 * Kullanıcı engelini kaldır
 */
function unbanUser($user_id) {
    global $db;
    if (!isAdmin()) return false;
    
    $stmt = $db->prepare("DELETE FROM user_bans WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * Ziyaretçi istatistiklerini getir
 */
function getVisitorStats($period = 'daily') {
    global $db;
    if (!isAdmin()) return [];
    
    $sql = "
        SELECT 
            DATE(visited_at) as date,
            COUNT(DISTINCT visitor_ip) as unique_visitors,
            COUNT(*) as total_visits
        FROM visitor_stats
    ";
    
    switch ($period) {
        case 'daily':
            $sql .= " WHERE visited_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
            break;
        case 'weekly':
            $sql .= " WHERE visited_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 WEEK)";
            break;
        case 'monthly':
            $sql .= " WHERE visited_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)";
            break;
    }
    
    $sql .= " GROUP BY DATE(visited_at) ORDER BY date DESC";
    
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * En çok izlenen içerikleri getir
 */
function getMostViewedContents($limit = 10, $period = null) {
    global $db;
    
    $sql = "
        SELECT 
            c.*,
            COUNT(cv.id) as view_count,
            GROUP_CONCAT(cat.name) as categories
        FROM contents c
        LEFT JOIN content_views cv ON c.id = cv.content_id
        LEFT JOIN content_categories cc ON c.id = cc.content_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
    ";
    
    if ($period) {
        $sql .= " WHERE cv.viewed_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)";
    }
    
    $sql .= "
        GROUP BY c.id
        ORDER BY view_count DESC
        LIMIT ?
    ";
    
    $stmt = $db->prepare($sql);
    
    if ($period) {
        $stmt->execute([$period, $limit]);
    } else {
        $stmt->execute([$limit]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * En aktif kullanıcıları getir
 */
function getMostActiveUsers($limit = 10, $period = null) {
    global $db;
    
    $sql = "
        SELECT 
            u.*,
            COUNT(DISTINCT ua.id) as activity_count,
            COUNT(DISTINCT c.id) as comment_count,
            COUNT(DISTINCT cv.id) as view_count
        FROM users u
        LEFT JOIN user_activities ua ON u.id = ua.user_id
        LEFT JOIN comments c ON u.id = c.user_id
        LEFT JOIN content_views cv ON u.id = cv.user_id
    ";
    
    if ($period) {
        $sql .= " WHERE ua.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)";
    }
    
    $sql .= "
        GROUP BY u.id
        ORDER BY activity_count DESC
        LIMIT ?
    ";
    
    $stmt = $db->prepare($sql);
    
    if ($period) {
        $stmt->execute([$period, $limit]);
    } else {
        $stmt->execute([$limit]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Sayfa ziyaretlerini kaydet
 */
function trackPageView() {
    $ignored_paths = [
        '/assets/',
        '/uploads/',
        '/favicon.ico'
    ];
    
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Yoksayılan yolları kontrol et
    foreach ($ignored_paths as $path) {
        if (strpos($current_path, $path) === 0) {
            return;
        }
    }
    
    // Bot kontrolü
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $bot_keywords = ['bot', 'crawler', 'spider', 'slurp', 'mediapartners'];
    foreach ($bot_keywords as $keyword) {
        if (stripos($user_agent, $keyword) !== false) {
            return;
        }
    }
    
    // Ziyareti kaydet
    logVisit($current_path);
}

// Her sayfa yüklendiğinde ziyareti kaydet
trackPageView();

if (!function_exists('redirect')) {
    function redirect($path = '') {
        $base_url = BASE_URL;
        $url = $base_url . '/' . ltrim($path, '/');
        header("Location: $url");
        exit;
    }
}

/**
 * SEO URL oluştur
 */
function generateSeoUrl($title) {
    $tr = array('ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','Ç','ç');
    $eng = array('s','s','i','i','g','g','u','u','o','o','c','c');
    $title = str_replace($tr, $eng, $title);
    $title = strtolower($title);
    $title = preg_replace('/[^a-z0-9\s-]/', '', $title);
    $title = preg_replace('/\s+/', ' ', $title);
    $title = str_replace(' ', '-', $title);
    return $title;
}

/**
 * Meta başlık oluştur
 */
function generateMetaTitle($title, $site_name = null) {
    if (!$site_name) {
        $site_name = getSetting('site_name', 'Film & Dizi');
    }
    return $title . ' - ' . $site_name;
}

/**
 * Meta açıklama oluştur
 */
function generateMetaDescription($description, $length = 160) {
    $description = strip_tags($description);
    if (mb_strlen($description) > $length) {
        $description = mb_substr($description, 0, $length - 3) . '...';
    }
    return $description;
}

/**
 * Kullanıcıya rol atar
 */
function assignRole($user_id, $role_id, $assigned_by = null) {
    global $db;
    try {
        $stmt = $db->prepare("
            INSERT INTO user_role_relations (user_id, role_id, assigned_by) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $role_id, $assigned_by]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Kullanıcıdan rol alır
 */
function revokeRole($user_id, $role_id) {
    global $db;
    try {
        $stmt = $db->prepare("
            DELETE FROM user_role_relations 
            WHERE user_id = ? AND role_id = ?
        ");
        $stmt->execute([$user_id, $role_id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function addToWatchlist($userId, $contentId) {
    global $db;
    $stmt = $db->prepare("INSERT INTO watchlist (user_id, content_id) VALUES (?, ?)");
    return $stmt->execute([$userId, $contentId]);
}

function removeFromWatchlist($userId, $contentId) {
    global $db;
    $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND content_id = ?");
    return $stmt->execute([$userId, $contentId]);
}