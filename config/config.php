<?php
declare(strict_types=1);

// Affichage des erreurs (désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', '0');

date_default_timezone_set('Africa/Tunis');

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/gestion_archive');
define('UPLOAD_DIR', BASE_PATH . '/uploadsar');
define('UPLOAD_MAX_SIZE', 100 * 1024 * 1024); // 100 Mo

// Base de données
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'gestion_archive');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function (string $class): void {
    $file = BASE_PATH . '/includes/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/cache.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';
