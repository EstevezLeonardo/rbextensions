<?php

/**
 * Endpoint POST que marca uma venda como extornada (tabela `vendas`).
 * Usado pelo botão "Marcar como Estornada" de dashboard/financeiro.php.
 * Recebe JSON: { id, csrf_token }.
 *
 * Só muda o Status da venda pra "extornada" (passa a contar como
 * estorno no resumo financeiro, em vez de receita concluída) — não mexe
 * no estoque nem gera movimentação, já que o produto já saiu
 * fisicamente; ver a doc de App\Entity\Venda::marcarComoExtornada().
 * Vendas já extornadas não podem ser extornadas de novo.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Venda;
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
    echo json_encode(['erro' => 'Venda inválida.']);
    exit;
}

$venda = Venda::getVenda((int) $dados['id']);

if (!$venda instanceof Venda) {
    http_response_code(422);
    echo json_encode(['erro' => 'Venda não encontrada.']);
    exit;
}

if ($venda->Status === 'extornada') {
    http_response_code(422);
    echo json_encode(['erro' => 'Essa venda já está extornada.']);
    exit;
}

$venda->marcarComoExtornada();

echo json_encode(['sucesso' => true]);
