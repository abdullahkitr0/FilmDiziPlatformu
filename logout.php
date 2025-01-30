<?php
session_start();

// Oturumu temizle
session_unset();
session_destroy();

// Ana sayfaya yönlendir
header('Location: /');
exit; 