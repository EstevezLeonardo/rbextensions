<?php

/**
 * Endpoint POST que exclui uma compra de produtos (tabela
 * `compras_produtos`). Usado pelo botão "Excluir" de cada item do card
 * "Compra de Produtos" em dashboard/estoque.php. Recebe JSON: { id,
 * csrf_token }.
 *
 * Como a compra não mexe em estoque (ver a doc de
 * app/Entity/CompraProduto.php), excluir também não desfaz nada além
 * do próprio registro financeiro.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\CompraProduto;
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
    echo json_encode(['erro' => 'Compra inválida.']);
    exit;
}

$compra = new CompraProduto();
$compra->id = (int) $dados['id'];
$compra->excluir();

echo json_encode(['sucesso' => true]);
