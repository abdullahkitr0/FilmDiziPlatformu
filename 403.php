<?php
http_response_code(403);
$page_title = "Erişim Reddedildi";
require_once 'includes/header.php';
?>

<div class="container-xl py-4">
    <div class="empty">
        <div class="empty-img">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock" width="128" height="128" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z"></path>
                <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0"></path>
                <path d="M8 11v-4a4 4 0 1 1 8 0v4"></path>
            </svg>
        </div>
        <p class="empty-title">Erişim Reddedildi</p>
        <p class="empty-subtitle text-muted">
            Bu sayfaya erişim yetkiniz bulunmuyor.
            <?php if (!isLoggedIn()): ?>
                Lütfen giriş yapın veya kayıt olun.
            <?php endif; ?>
        </p>
        <div class="empty-action">
            <?php if (isLoggedIn()): ?>
                <a href="/" class="btn btn-primary">
                    Ana Sayfaya Dön
                </a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-primary me-2">
                    Giriş Yap
                </a>
                <a href="/register.php" class="btn btn-secondary">
                    Kayıt Ol
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 