<?php
/**
 * Logout do Sistema
 */

require_once __DIR__ . '/config.php';

// Limpa todas as variáveis da sessão
$_SESSION = [];

// Destrói o cookie de sessão se necessário
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Redireciona para o login
header('Location: login.php');
exit;
