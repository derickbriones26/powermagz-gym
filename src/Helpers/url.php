<?php
function base_path(): string
{
    static $base = null;
    if ($base !== null) return $base;
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return $base;
}

function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function url_e(string $path = '/'): string
{
    return htmlspecialchars(url($path), ENT_QUOTES, 'UTF-8');
}
