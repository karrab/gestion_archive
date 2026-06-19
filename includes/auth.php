<?php
declare(strict_types=1);

function auth_attempt(string $login, string $password): bool
{
    $stmt = db()->prepare('SELECT id, nom, prenom, password, role_id, actif FROM user WHERE login = ? LIMIT 1');
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if (!$user || !$user['actif'] || !password_verify($password, $user['password'])) {
        return false;
    }

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_nom'] = $user['nom'];
    $_SESSION['user_prenom'] = $user['prenom'];
    $_SESSION['role_id'] = (int) $user['role_id'];
    $_SESSION['permissions'] = load_permissions((int) $user['role_id']);

    session_regenerate_id(true);
    log_historique('connexion', 'auth', 'Connexion réussie de ' . $login);

    return true;
}

function load_permissions(int $roleId): array
{
    $stmt = db()->prepare(
        'SELECT p.code FROM permission p
         INNER JOIN role_permission rp ON rp.permission_id = p.id
         WHERE rp.role_id = ?'
    );
    $stmt->execute([$roleId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function auth_logout(): void
{
    log_historique('deconnexion', 'auth', 'Déconnexion');
    $_SESSION = [];
    session_destroy();
}

function auth_check(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function has_permission(string $code): bool
{
    return in_array($code, $_SESSION['permissions'] ?? [], true);
}

function require_permission(string $code): void
{
    auth_check();
    if (!has_permission($code)) {
        http_response_code(403);
        echo 'Accès refusé.';
        exit;
    }
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}
