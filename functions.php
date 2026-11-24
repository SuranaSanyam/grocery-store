<?php
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function csrf_field(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

function verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}

function require_role(string $role): void
{
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== $role) {
        redirect($role === 'admin' ? 'admin_login.php' : 'login.php');
    }
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function show_flash(): void
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($flash['type']) . ' mb-4" role="alert">' . e($flash['message']) . '</div>';
    }
}

function image_url(?string $filename): string
{
    if ($filename && basename($filename) === $filename && is_file(__DIR__ . '/upload/' . $filename)) {
        return 'upload/' . rawurlencode($filename);
    }
    return '';
}

function store_uploaded_image(string $field, ?string &$error = null): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 5 * 1024 * 1024) {
        $error = 'Choose an image smaller than 5 MB.';
        return null;
    }

    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/svg' => 'svg',
    ];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->file($_FILES[$field]['tmp_name']);
    if (!isset($mimeToExtension[$mime])) {
        $error = 'Images must be JPG, PNG, WEBP, or SVG files.';
        return null;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $mimeToExtension[$mime];
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], __DIR__ . '/upload/' . $filename)) {
        $error = 'The image could not be saved. Check the upload folder permissions.';
        return null;
    }
    return $filename;
}

function get_cart_count(): int
{
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

