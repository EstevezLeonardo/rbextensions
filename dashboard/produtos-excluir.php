<?php

/**
 * Endpoint POST que exclui um produto (tabela `produtos`). Usado pelo
 * botão "Excluir" de cada item da lista de resultados de
 * dashboard/controle-produtos.php. Recebe JSON: { id, csrf_token }.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Produto;
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
    echo json_encode(['erro' => 'Produto inválido.']);
    exit;
}

$produto = new Produto();
$produto->id = (int) $dados['id'];
$produto->excluir();

echo json_encode(['sucesso' => true]);
