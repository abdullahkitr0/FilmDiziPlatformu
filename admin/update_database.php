<?php
require_once '../config/database.php';

// Global değişken olarak tanımla
global $db;

function handleError($e) {
    global $db;
    try {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
    } catch (Exception $ex) {
        // Transaction hatası görmezden gel
    }
    echo "Hata: " . $e->getMessage();
}

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Foreign key kontrollerini kapat
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // İlişkili tabloları sil
    $db->exec("DROP TABLE IF EXISTS role_permissions");
    $db->exec("DROP TABLE IF EXISTS user_role_relations");
    $db->exec("DROP TABLE IF EXISTS user_roles_backup");
    $db->exec("DROP TABLE IF EXISTS user_roles");
    $db->exec("DROP TABLE IF EXISTS user_activities");
    $db->exec("DROP TABLE IF EXISTS user_bans");
    
    // Foreign key kontrollerini aç
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Kullanıcı rolleri tablosunu oluştur
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Rol izinleri tablosu
    $db->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            role_id INT NOT NULL,
            permission_name VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES user_roles(id) ON DELETE CASCADE,
            UNIQUE KEY role_permission_unique (role_id, permission_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Kullanıcı-rol ilişki tablosu
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_role_relations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            role_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES user_roles(id) ON DELETE CASCADE,
            UNIQUE KEY user_role_unique (user_id, role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Kullanıcı aktiviteleri tablosu
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_activities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            activity_type VARCHAR(50) NOT NULL,
            activity_details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Admin logları tablosu
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(50) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Kullanıcı engelleme tablosu
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_bans (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            reason TEXT NOT NULL,
            banned_by INT NOT NULL,
            banned_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            removed_at TIMESTAMP NULL,
            removed_by INT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (removed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Site ayarları tablosu
    $db->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
            is_public BOOLEAN DEFAULT FALSE,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Varsayılan rolleri ekle
    $roles = [
        ['admin', 'Tam yetkili yönetici'],
        ['moderator', 'İçerik moderatörü'],
        ['editor', 'İçerik editörü'],
        ['user', 'Normal kullanıcı']
    ];
    
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT IGNORE INTO user_roles (name, description) VALUES (?, ?)");
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
        $db->commit();
    } catch (Exception $e) {
        handleError($e);
        exit;
    }
    
    // Varsayılan site ayarlarını ekle
    $settings = [
        ['site_name', 'Site Adı', 'general', true],
        ['site_description', 'Site açıklaması', 'general', true],
        ['contact_email', 'iletisim@example.com', 'general', true],
        ['facebook_url', '', 'social', true],
        ['twitter_url', '', 'social', true],
        ['instagram_url', '', 'social', true],
        ['smtp_host', '', 'email', false],
        ['smtp_port', '', 'email', false],
        ['smtp_username', '', 'email', false],
        ['smtp_password', '', 'email', false],
        ['maintenance_mode', '0', 'system', false],
        ['user_registration', '1', 'system', false],
        ['comment_approval', '1', 'system', false]
    ];
    
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_group, is_public) VALUES (?, ?, ?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        $db->commit();
    } catch (Exception $e) {
        handleError($e);
        exit;
    }
    
    echo "Veritabanı başarıyla güncellendi.";
} catch (PDOException $e) {
    handleError($e);
} 