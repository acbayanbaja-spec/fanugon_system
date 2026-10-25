<?php
/**
 * MedCare System - Old login URL
 * The login form now lives in index.php. Keep this file so old links and
 * bookmarks still work (including ?registered=1).
 */
require_once __DIR__ . '/../../config/config.php';

$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . BASE_URL . '/index.php' . ($query !== '' ? '?' . $query : ''), true, 301);
exit();
