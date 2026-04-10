<?php

// This router is for the PHP built-in web server
$file = __DIR__ . $_SERVER['REQUEST_URI'];

// If it's a static file (actual file exists), serve it directly
if (is_file($file) && is_readable($file)) {
    return false;
}

// Otherwise route through Symfony
require_once __DIR__ . '/index.php';

