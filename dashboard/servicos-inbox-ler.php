<?php

/**
 * Endpoint JSON: busca o corpo completo de uma mensagem específica do
 * usuário logado, via API do Gmail (App\Mail\GmailApi). Consumido pelo
 * botão "Ler" da lista de dashboard/servicos.php.
 *
 * Aceita via querystring:
 *   - uid: id da mensagem na API do Gmail (obrigatório; o mesmo "uid"
 *          devolvido por dashboard/servicos-inbox-listar.php — a API
 *          do Gmail usa um id global, não precisa saber de qual pasta
 *          a mensagem é)
 *
 * Devolve { uid, de, assunto, data, corpo } ou { erro }.
 *
 * Exige login e que o usuário já tenha conectado a conta do Gmail.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Mail\GoogleOAuth;
use App\Mail\GmailApi;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

if (empty($_GET['uid'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Mensagem inválida.']);
    exit;
}

$usuarioLogado = Login::getUsuario();

try {
    $accessToken = GoogleOAuth::obterAccessTokenParaUsuario($usuarioLogado->google_refresh_token);
    $mensagem = (new GmailApi($accessToken))->ler($_GET['uid']);
    echo json_encode($mensagem);
} catch (\RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['erro' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('Erro ao ler mensagem: '.$e->getMessage());
    http_response_code(502);
    echo json_encode(['erro' => 'Não foi possível abrir essa mensagem.']);
}
