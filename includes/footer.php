<?php
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
?>
    </div><!-- page-wrapper end -->
    <footer class="footer py-4">
        <div class="container">
            <div class="row g-4 py-4">
                <div class="col-12 col-md-4">
                    <h5 class="text-primary mb-3"><?= SITE_NAME ?></h5>
                    <p class="text-secondary mb-0">
                    Film ve dizi tutkunları için özel olarak tasarlanmış platformumuzda, en yeni içerikleri keşfedin, izleme listenizi oluşturun ve deneyimlerinizi paylaşın.
                    </p>
                </div>
                <div class="col-6 col-md-4">
                    <h5 class="text-primary mb-3">Hızlı Bağlantılar</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= url() ?>" class="footer-link">Ana Sayfa</a></li>
                        <li><a href="<?= url('latest.php') ?>" class="footer-link">Son Eklenenler</a></li>
                        <li><a href="<?= url('top-rated.php') ?>" class="footer-link">En İyiler</a></li>
                        <li><a href="<?= url('search.php') ?>" class="footer-link">Arama</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4">
                    <h5 class="text-primary mb-3">Sosyal Medya</h5>
                    <div class="d-flex gap-2">
                        <a href="#" class="social-link" title="Discord">
                            <i class="fab fa-discord"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-top border-secondary pt-4">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start text-secondary">
                        &copy; <?= date('Y') ?> <?= SITE_NAME ?>. Tüm hakları saklıdır.
                    </div>
                    <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="#" class="footer-link">Gizlilik Politikası</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="#" class="footer-link">Kullanım Şartları</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="#" class="footer-link">İletişim</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    </div><!-- page end -->

    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html> 