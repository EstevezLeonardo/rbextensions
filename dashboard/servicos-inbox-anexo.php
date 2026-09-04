<?php

/**
 * Baixa um anexo de uma mensagem do usuário logado, via API do Gmail
 * (App\Mail\GmailApi::buscarAnexo). Consumido pelos links de anexo
 * listados por dashboard/servicos-inbox-ler.php — navegação normal
 * (não AJAX), pra o navegador tratar como download de arquivo.
 *
 * Aceita via querystring:
 *   - uid: id da mensagem (o mesmo devolvido por servicos-inbox-ler.php)
 *   - attachmentId: id do anexo dentro da mensagem (idem)
 *   - nome, tipo: nome/mimetype do anexo (idem), só pra montar os
 *     cabeçalhos de download — a API do Gmail não devolve isso junto
 *     do conteúdo bruto do anexo
 *
 * Exige login e que o usuário já tenha conectado a conta do Gmail.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Mail\GoogleOAuth;
use App\Mail\GmailApi;

Login::requireLogin();

if (empty($_GET['uid']) || empty($_GET['attachmentId'])) {
    http_response_code(422);
    exit('Anexo inválido.');
}

$usuarioLogado = Login::getUsuario();

$nome = preg_replace('/[\r\n]/', '', $_GET['nome'] ?? 'anexo');
$tipo = preg_match('#^[\w.+-]+/[\w.+-]+$#', $_GET['tipo'] ?? '') ? $_GET['tipo'] : 'application/octet-stream';

try {
    $accessToken = GoogleOAuth::obterAccessTokenParaUsuario($usuarioLogado->google_refresh_token);
    $bytes = (new GmailApi($accessToken))->buscarAnexo($_GET['uid'], $_GET['attachmentId']);

    header('Content-Type: '.$tipo);
    header('Content-Length: '.strlen($bytes));
    header('Content-Disposition: attachment; filename="'.basename($nome).'"; filename*=UTF-8\'\''.rawurlencode($nome));
    echo $bytes;
} catch (\RuntimeException $e) {
    http_response_code(422);
    exit($e->getMessage());
} catch (\Throwable $e) {
    error_log('Erro ao baixar anexo: '.$e->getMessage());
    http_response_code(502);
    exit('Não foi possível baixar o anexo.');
}
