<?php

/**
 * Endpoint POST que cria um evento (tabela `eventos`) a partir do
 * formulário "Adicionar Evento" da agenda (dashboard/agenda.php).
 *
 * Chamado via fetch pelo dashboard/public/assets/js/agenda.js, com o
 * corpo em JSON: { titulo, inicio, fim, csrf_token }. Devolve o
 * evento criado em JSON (já no formato title/start/end do
 * FullCalendar) em caso de sucesso, ou {erro: "..."} com um status
 * HTTP de erro caso contrário.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Evento;
use App\Session\Login;
use App\Session\Csrf;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

// o fetch manda o corpo como JSON (não como formulário tradicional),
// então lemos direto do corpo bruto da requisição em vez de $_POST
$dados = json_decode(file_get_contents('php://input'), true) ?? [];

if (!Csrf::validate($dados['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sessão expirada. Recarregue a página e tente novamente.']);
    exit;
}

if (empty($dados['titulo']) || empty($dados['inicio']) || empty($dados['fim'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Preencha título, início e fim do evento.']);
    exit;
}

if ($dados['fim'] < $dados['inicio']) {
    http_response_code(422);
    echo json_encode(['erro' => 'A data de fim não pode ser antes da data de início.']);
    exit;
}

$evento = new Evento();
$evento->Titulo = $dados['titulo'];
// o <input type="datetime-local"> manda "AAAA-MM-DDTHH:MM"; o MySQL
// espera "AAAA-MM-DD HH:MM:SS" (espaço em vez de "T"; sem segundos,
// o MySQL assume 00 sozinho)
$evento->Inicio = str_replace('T', ' ', $dados['inicio']);
$evento->Fim = str_replace('T', ' ', $dados['fim']);
$evento->cadastrar();

echo json_encode([
    'id' => $evento->id,
    'title' => $evento->Titulo,
    'start' => $dados['inicio'],
    'end' => $dados['fim'],
]);
