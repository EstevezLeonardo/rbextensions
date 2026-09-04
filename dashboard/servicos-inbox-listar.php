<?php

/**
 * Endpoint JSON: lista as mensagens de uma pasta de e-mail do usuário
 * logado, via API do Gmail (App\Mail\GmailApi), mais recentes
 * primeiro. Consumido por dashboard/servicos.php (ver
 * dashboard/src/servicos.ts).
 *
 * A API do Gmail só pagina "pra frente" (token opaco, sem número de
 * página) — por isso o front-end guarda os tokens de cada página já
 * visitada e manda o token da que quer ver.
 *
 * Aceita via querystring:
 *   - pasta:       'caixa' (padrão), 'enviados', 'rascunhos' ou 'lixeira'
 *   - page_token:  token da página a buscar (vazio = primeira página)
 *   - data_inicio: filtra por essa data em diante ("Y-m-d")
 *   - data_fim:    junto com data_inicio, filtra pelo período (inclusive)
 *   - busca:       filtra por remetente OU assunto contendo o termo
 *
 * Devolve { mensagens, proximoPageToken } ou { erro }.
 *
 * Exige login e que o usuário já tenha conectado a conta do Gmail.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Mail\GoogleOAuth;
use App\Mail\GmailApi;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$usuarioLogado = Login::getUsuario();

$pasta = $_GET['pasta'] ?? 'caixa';
$pageToken = trim($_GET['page_token'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim = trim($_GET['data_fim'] ?? '');
$busca = trim($_GET['busca'] ?? '');

try {
    $accessToken = GoogleOAuth::obterAccessTokenParaUsuario($usuarioLogado->google_refresh_token);
    $resultado = (new GmailApi($accessToken))->listar($pasta, $pageToken ?: null, 6, $dataInicio ?: null, $dataFim ?: null, $busca ?: null);

    echo json_encode([
        'mensagens' => $resultado['mensagens'],
        'proximoPageToken' => $resultado['proximoPageToken'],
    ]);
} catch (\RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['erro' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('Erro ao listar pasta de e-mail: '.$e->getMessage());
    http_response_code(502);
    echo json_encode(['erro' => 'Não foi possível acessar essa pasta agora.']);
}
