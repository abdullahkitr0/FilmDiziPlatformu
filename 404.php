<?php
http_response_code(404);
$page_title = "Sayfa Bulunamadı";
require_once 'includes/header.php';
?>

<div class="container-xl py-4">
    <div class="empty">
        <div class="empty-img">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-error-404" width="128" height="128" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M3 7v4a1 1 0 0 0 1 1h3"></path>
                <path d="M7 7v10"></path>
                <path d="M10 8v8a1 1 0 0 0 1 1h2a1 1 0 0 0 1 -1v-8a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1z"></path>
                <path d="M17 7v4a1 1 0 0 0 1 1h3"></path>
                <path d="M21 7v10"></path>
            </svg>
        </div>
        <p class="empty-title">Sayfa Bulunamadı</p>
        <p class="empty-subtitle text-muted">
            Aradığınız sayfa bulunamadı veya taşınmış olabilir.
        </p>
        <div class="empty-action">
            <a href="/" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-home" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M5 12l-2 0l9 -9l9 9l-2 0"></path>
                    <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"></path>
                    <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"></path>
                </svg>
                Ana Sayfaya Dön
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 