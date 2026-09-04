<?php

/**
 * Endpoint POST que envia um e-mail via API do Gmail (App\Mail\GmailApi),
 * autenticando com o access token obtido a partir do refresh token do
 * usuário logado (App\Mail\GoogleOAuth). Recebe multipart/form-data
 * (não JSON — precisa disso pra poder vir com arquivo anexado):
 * destinatario, assunto, mensagem, csrf_token e, opcionalmente,
 * anexos[] (um ou mais arquivos, de qualquer tipo).
 *
 * Exige login e que o usuário já tenha conectado a conta do Gmail
 * (botão "Conectar E-mail" de dashboard/servicos.php).
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Session\Csrf;
use App\Mail\GoogleOAuth;
use App\Mail\GmailApi;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sessão expirada. Recarregue a página e tente novamente.']);
    exit;
}

$destinatario = trim($_POST['destinatario'] ?? '');
$assunto = trim($_POST['assunto'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

if ($destinatario === '' || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['erro' => 'Informe um e-mail de destino válido.']);
    exit;
}

if ($assunto === '' || $mensagem === '') {
    http_response_code(422);
    echo json_encode(['erro' => 'Preencha o assunto e a mensagem.']);
    exit;
}

$anexos = [];
foreach (($_FILES['anexos']['error'] ?? []) as $indice => $erro) {
    if ($erro === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    if ($erro !== UPLOAD_ERR_OK) {
        http_response_code(422);
        echo json_encode(['erro' => 'Não foi possível enviar o anexo "'.$_FILES['anexos']['name'][$indice].'".']);
        exit;
    }
    $anexos[] = [
        'nome' => $_FILES['anexos']['name'][$indice],
        'tipo' => $_FILES['anexos']['type'][$indice],
        'conteudo' => file_get_contents($_FILES['anexos']['tmp_name'][$indice]),
    ];
}

$usuarioLogado = Login::getUsuario();

try {
    $accessToken = GoogleOAuth::obterAccessTokenParaUsuario($usuarioLogado->google_refresh_token);
    (new GmailApi($accessToken))->enviar($destinatario, $assunto, $mensagem, $anexos);
    echo json_encode(['sucesso' => true]);
} catch (\RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['erro' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('Erro ao enviar e-mail: '.$e->getMessage());
    http_response_code(502);
    echo json_encode(['erro' => 'Não foi possível enviar o e-mail agora.']);
}
