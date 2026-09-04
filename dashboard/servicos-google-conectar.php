<?php

/**
 * Redireciona pra tela de consentimento do Google (botão "Conectar
 * E-mail" de dashboard/servicos.php — link normal, não AJAX, porque
 * precisa navegar de verdade pro Google e voltar).
 *
 * Guarda um "state" aleatório na sessão, conferido de volta em
 * servicos-google-callback.php pra garantir que a resposta realmente
 * veio de um redirecionamento que a gente iniciou (proteção contra
 * CSRF no fluxo OAuth).
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Mail\GoogleOAuth;

Login::requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

header('Location: '.GoogleOAuth::urlDeAutorizacao($state));
exit;
