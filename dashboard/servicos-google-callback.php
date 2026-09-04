<?php

/**
 * Callback do OAuth do Google (URI de redirecionamento configurada no
 * Google Cloud Console). O Google manda a pessoa de volta pra cá
 * depois da tela de consentimento, com ?code=... (sucesso) ou
 * ?error=... (recusou/cancelou).
 *
 * Troca o code por tokens (App\Mail\GoogleOAuth::trocarCodigoPorTokens),
 * guarda o refresh token criptografado no usuário logado
 * (App\Entity\Vaga::salvarGoogleRefreshToken) e volta pra
 * dashboard/servicos.php.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Mail\GoogleOAuth;
use App\Mail\Crypto;

Login::requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$stateEsperado = $_SESSION['google_oauth_state'] ?? null;
unset($_SESSION['google_oauth_state']);

if (isset($_GET['error'])) {
    header('Location: servicos.php?email_erro=cancelado');
    exit;
}

if (empty($_GET['code']) || empty($_GET['state']) || !hash_equals((string) $stateEsperado, $_GET['state'])) {
    header('Location: servicos.php?email_erro=sessao');
    exit;
}

try {
    $tokens = GoogleOAuth::trocarCodigoPorTokens($_GET['code']);

    if (empty($tokens['refresh_token'])) {
        // acontece se a pessoa já tinha conectado antes e o Google não reemitiu
        // um refresh token novo — pedimos pra tentar de novo (prompt=consent
        // deveria sempre reemitir, mas fica essa rede de segurança)
        header('Location: servicos.php?email_erro=sem_refresh_token');
        exit;
    }

    Login::getUsuario()->salvarGoogleRefreshToken(Crypto::encrypt($tokens['refresh_token']));

    header('Location: servicos.php?email_conectado=1');
    exit;
} catch (\Throwable $e) {
    error_log('Erro ao trocar código OAuth do Google: '.$e->getMessage());
    header('Location: servicos.php?email_erro=google');
    exit;
}
