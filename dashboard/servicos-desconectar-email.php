<?php

/**
 * Endpoint POST que "desloga" o usuário logado do Gmail: revoga o
 * token no Google (best-effort — se falhar, desconecta localmente
 * mesmo assim) e apaga o refresh token salvo
 * (App\Entity\Vaga::$google_refresh_token). Depois disso, enviar
 * e-mail ou acessar qualquer pasta volta a pedir pra conectar de novo
 * (botão "Conectar E-mail" de dashboard/servicos.php, que passa pelo
 * consentimento do Google outra vez). Recebe JSON: { csrf_token }.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Session\Csrf;
use App\Mail\Crypto;
use App\Mail\GoogleOAuth;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

if (!Csrf::validate($dados['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sessão expirada. Recarregue a página e tente novamente.']);
    exit;
}

$usuarioLogado = Login::getUsuario();

$refreshToken = Crypto::decrypt($usuarioLogado->google_refresh_token);
if ($refreshToken !== null) {
    GoogleOAuth::revogar($refreshToken);
}

$usuarioLogado->salvarGoogleRefreshToken(null);

echo json_encode(['sucesso' => true]);
