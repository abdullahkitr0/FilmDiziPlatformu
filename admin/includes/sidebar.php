<!-- Sidebar -->
<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="../index.php">
                <img src="../static/logo.svg" width="110" height="32" alt="Logo" class="navbar-brand-image">
            </a>
        </h1>
        
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <polyline points="5 12 3 12 12 3 21 12 19 12" />
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Gösterge Paneli
                        </span>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#navbar-content" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M8 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                <rect x="8" y="3" width="8" height="4" rx="1" />
                                <line x1="8" y1="12" x2="16" y2="12" />
                                <line x1="8" y1="16" x2="16" y2="16" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            İçerik Yönetimi
                        </span>
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="contents.php">
                            İçerikler
                        </a>
                        <a class="dropdown-item" href="categories.php">
                            Kategoriler
                        </a>
                        <a class="dropdown-item" href="platforms.php">
                            Platformlar
                        </a>
                        <a class="dropdown-item" href="bulk-content.php">
                            Toplu İçerik İşlemleri
                        </a>
                        <a class="dropdown-item" href="add-content-omdb.php">
                            OmdbApi
                        </a>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="media-library.php">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <line x1="15" y1="8" x2="15.01" y2="8" />
                                <rect x="4" y="4" width="16" height="16" rx="3" />
                                <path d="M4 15l4 -4a3 5 0 0 1 3 0l5 5" />
                                <path d="M14 14l1 -1a3 5 0 0 1 3 0l2 2" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Medya Kütüphanesi
                        </span>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#navbar-users" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="9" cy="7" r="4" />
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Kullanıcı Yönetimi
                        </span>
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="users.php">
                            Kullanıcılar
                        </a>
                        <a class="dropdown-item" href="user-form.php">
                            Kullanıcı Ekle
                        </a>
                       
                        <a class="dropdown-item" href="user-roles.php">
                            Roller ve İzinler
                        </a>
                        <a class="dropdown-item" href="user-activities.php">
                            Aktivite Logları
                        </a>
                        <a class="dropdown-item" href="user-bans.php">
                            Engellemeler
                        </a>
                    </div>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#navbar-seo" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="10" cy="10" r="7" />
                                <line x1="21" y1="21" x2="15" y2="15" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            SEO ve İstatistikler
                        </span>
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="seo-settings.php">
                            SEO Ayarları
                        </a>
                        <a class="dropdown-item" href="statistics.php">
                            İstatistikler
                        </a>
                        <a class="dropdown-item" href="admin-logs.php">
                            Admin Logları
                        </a>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Site Ayarları
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside> 