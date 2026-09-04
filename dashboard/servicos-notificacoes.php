<?php

/**
 * Endpoint JSON: quantos e-mails "novos" mostrar no sino de
 * notificações do cabeçalho (usado em toda página do dashboard, ver
 * dashboard/src/notificacoes.ts) — não é literalmente o "não lidas" do
 * Gmail, e sim quantos chegaram desde a última vez que a pessoa entrou
 * em dashboard/servicos.php, que zera essa contagem (ver lá).
 * Guardado na sessão, não no banco: $_SESSION['email_naolidas_base'].
 *
 * Devolve { naoLidas } — sempre 0 se a conta do Gmail não estiver
 * conectada ou se a chamada ao Gmail falhar (não é motivo pra mostrar
 * erro pra quem só quer ver o restante do painel).
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Mail\GoogleOAuth;
use App\Mail\GmailApi;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$usuarioLogado = Login::getUsuario();

if (empty($usuarioLogado->google_refresh_token)) {
    echo json_encode(['naoLidas' => 0]);
    exit;
}

try {
    $accessToken = GoogleOAuth::obterAccessTokenParaUsuario($usuarioLogado->google_refresh_token);
    $naoLidasAgora = (new GmailApi($accessToken))->contarNaoLidas();
} catch (\Throwable $e) {
    echo json_encode(['naoLidas' => 0]);
    exit;
}

$base = $_SESSION['email_naolidas_base'] ?? $naoLidasAgora;
echo json_encode(['naoLidas' => max(0, $naoLidasAgora - $base)]);
