<?php
declare(strict_types=1);

function log_historique(string $action, string $module, string $description = ''): void
{
    $stmt = db()->prepare(
        'INSERT INTO historique (user_id, action, module, description, ip_address) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        current_user_id(),
        $action,
        $module,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Jeton CSRF invalide.');
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Génère un numéro séquentiel par année, ex: PV/2026/00001 ou BR/2026/00001.
 */
function generate_numero(string $prefixe): string
{
    $annee = (int) date('Y');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT dernier_numero FROM compteur_numero WHERE cle = ? AND annee = ? FOR UPDATE');
        $stmt->execute([$prefixe, $annee]);
        $row = $stmt->fetch();

        if ($row === false) {
            $numero = 1;
            $pdo->prepare('INSERT INTO compteur_numero (cle, annee, dernier_numero) VALUES (?, ?, ?)')
                ->execute([$prefixe, $annee, $numero]);
        } else {
            $numero = (int) $row['dernier_numero'] + 1;
            $pdo->prepare('UPDATE compteur_numero SET dernier_numero = ? WHERE cle = ? AND annee = ?')
                ->execute([$numero, $prefixe, $annee]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return sprintf('%s/%d/%05d', $prefixe, $annee, $numero);
}

function get_parametres(): array
{
    return cache_remember('parametres', 600, function () {
        $row = db()->query('SELECT * FROM parametres ORDER BY id LIMIT 1')->fetch();
        return $row ?: [];
    });
}

/**
 * Pagination Keyset (sans OFFSET) : on pagine sur (sortCol, idCol) pour rester
 * performant même avec de gros volumes, conformément aux index composites.
 * $where est un tableau de conditions SQL (sans le mot-clé WHERE) déjà liées à $params.
 * Retourne ['items' => [...], 'has_more' => bool].
 */
function keyset_paginate(PDO $pdo, string $selectFromSql, array $where, array $params, string $sortCol, string $dir, string $idCol, ?string $cursorVal, ?int $cursorId, int $limit): array
{
    $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
    $op = $dir === 'DESC' ? '<' : '>';

    if ($cursorVal !== null && $cursorId !== null) {
        $where[] = "({$sortCol} {$op} :cursor_val OR ({$sortCol} = :cursor_val AND {$idCol} {$op} :cursor_id))";
        $params['cursor_val'] = $cursorVal;
        $params['cursor_id'] = $cursorId;
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "{$selectFromSql} {$whereSql} ORDER BY {$sortCol} {$dir}, {$idCol} {$dir} LIMIT " . ((int) $limit + 1);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $hasMore = count($rows) > $limit;
    if ($hasMore) {
        array_pop($rows);
    }

    return ['items' => $rows, 'has_more' => $hasMore];
}

/**
 * Construit une querystring en fusionnant $_GET avec les overrides fournis
 * (une valeur null supprime la clé), utile pour les liens "page suivante".
 */
function pagination_url(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === null) {
            unset($params[$k]);
        }
    }
    return '?' . http_build_query($params);
}

function upload_fichier(array $file, string $subDir = ''): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur de téléchargement du fichier.');
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new RuntimeException('Le fichier dépasse la taille maximale autorisée (100 Mo).');
    }

    $dir = UPLOAD_DIR . ($subDir ? '/' . trim($subDir, '/') : '');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nomFichier = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
    $destination = $dir . '/' . $nomFichier;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Impossible d\'enregistrer le fichier.');
    }

    return ($subDir ? trim($subDir, '/') . '/' : '') . $nomFichier;
}
