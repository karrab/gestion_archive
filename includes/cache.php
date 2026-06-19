<?php
declare(strict_types=1);

/**
 * Couche de cache APCu pour les listes de référence (service, employe, depot...).
 * OPcache se configure côté php.ini (voir config/opcache.ini) ; rien à coder ici.
 */
function cache_get(string $key)
{
    if (!function_exists('apcu_fetch')) {
        return false;
    }
    $value = apcu_fetch($key, $success);
    return $success ? $value : false;
}

function cache_set(string $key, $value, int $ttl = 300): void
{
    if (function_exists('apcu_store')) {
        apcu_store($key, $value, $ttl);
    }
}

function cache_delete(string $key): void
{
    if (function_exists('apcu_delete')) {
        apcu_delete($key);
    }
}

function cache_remember(string $key, int $ttl, callable $callback)
{
    $value = cache_get($key);
    if ($value !== false) {
        return $value;
    }
    $value = $callback();
    cache_set($key, $value, $ttl);
    return $value;
}
