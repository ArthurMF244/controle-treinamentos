<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

$_SESSION = [];
session_destroy();
redirect('login.php');
