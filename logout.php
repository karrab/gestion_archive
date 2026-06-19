<?php
declare(strict_types=1);
require __DIR__ . '/config/config.php';
auth_logout();
redirect(BASE_URL . '/login.php');
