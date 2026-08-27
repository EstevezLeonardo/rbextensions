<?php

/**
 * Endpoint POST que atualiza um evento existente (tabela `eventos`).
 * Usado quando a pessoa clica em "Editar" num item da lista de
 * resultados: a caixa "Adicionar Evento" vira um formulário de edição
 * (ver dashboard/public/assets/js/agenda.js) e, ao salvar, manda o
 * JSON aqui: { id, titulo, inicio, fim, csrf_token }.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Evento;
use App\Session\Login;
use App\Session\Csrf;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

if (!Csrf::validate($dados['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sessão expirada. Recarregue a página e tente novamente.']);
    exit;
}

if (empty($dados['id']) || !is_numeric($dados['id'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Evento inválido.']);
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
$evento->id = (int) $dados['id'];
$evento->Titulo = $dados['titulo'];
// mesma conversão de formato usada em eventos-criar.php: o
// <input type="datetime-local"> manda "T", o MySQL espera espaço
$evento->Inicio = str_replace('T', ' ', $dados['inicio']);
$evento->Fim = str_replace('T', ' ', $dados['fim']);
$evento->atualizar();

echo json_encode([
    'id' => $evento->id,
    'title' => $evento->Titulo,
    'start' => $dados['inicio'],
    'end' => $dados['fim'],
]);
