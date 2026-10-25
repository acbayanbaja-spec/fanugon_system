<?php
/**
 * MedCare System - Maintenance script guard
 *
 * Setup / diagnostic / password-reset scripts must never be reachable from
 * other computers on the network. Include this file at the very top of any
 * such script; it only allows requests coming from the server itself
 * (http://localhost/...).
 */
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
if (PHP_SAPI !== 'cli' && !in_array($remote_addr, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    die('Forbidden: maintenance scripts can only be opened on the server itself (http://localhost/...).');
}
