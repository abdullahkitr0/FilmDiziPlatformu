<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Gerekli dosyaları dahil et
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME ?></title>
    <meta name="description" content="<?= $page_description ?? SITE_DESCRIPTION ?>">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tabler.io CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
    <div class="page">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a class="navbar-brand" href="<?= url() ?>">
                    <?= SITE_NAME ?>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url() ?>">
                                <i class="fas fa-home me-2"></i>Ana Sayfa
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-film me-2"></i>Kategoriler
                            </a>
                            <div class="dropdown-menu">
                                <?php
                                try {
                                    $categories = getAllCategories();
                                    foreach ($categories as $category):
                                ?>
                                    <a class="dropdown-item" href="<?= url("category.php?slug={$category['slug']}") ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                <?php 
                                    endforeach;
                                } catch (Exception $e) {
                                    error_log("Kategori listesi yüklenirken hata: " . $e->getMessage());
                                }
                                ?>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('search.php') ?>">
                                <i class="fas fa-search me-2"></i>Arama
                            </a>
                        </li>
                    </ul>
                    
                    <div class="navbar-nav">
                        <?php if (isLoggedIn()): ?>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-2"></i>
                                    <?= htmlspecialchars($_SESSION['username']) ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="<?= url('profile.php') ?>">
                                        <i class="fas fa-user me-2"></i>Profil
                                    </a>
                                    <a class="dropdown-item" href="<?= url('watchlist.php') ?>">
                                        <i class="fas fa-list me-2"></i>İzleme Listem
                                    </a>
                                    <?php if (isAdmin()): ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?= url('admin/') ?>">
                                            <i class="fas fa-cog me-2"></i>Yönetim Paneli
                                        </a>
                                    <?php endif; ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?= url('logout.php') ?>">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="nav-item">
                                <a href="<?= url('login.php') ?>" class="nav-link">
                                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="<?= url('register.php') ?>" class="nav-link">
                                    <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="page-wrapper">
            <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="container">
                <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible mt-3">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?> 