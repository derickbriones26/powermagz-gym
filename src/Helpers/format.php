<?php
function peso(float|int|string|null $n): string
{
    return '₱' . number_format((float)($n ?? 0), 0);
}

function peso_decimal(float|int|string|null $n): string
{
    return '₱' . number_format((float)($n ?? 0), 2);
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function initials(?string $name): string
{
    $name = trim((string)$name);
    return $name ? strtoupper(mb_substr($name, 0, 1)) : '?';
}

function short_date(?string $date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : '—';
}

function time_short(?string $dt): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('g:i A', $ts) : '—';
}
