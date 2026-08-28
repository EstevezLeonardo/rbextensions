<?php

/**
 * Endpoint POST que exclui uma movimentação de estoque (tabela
 * `movimentacoes_estoque`). Usado pelo botão "Excluir" de cada item
 * do histórico de dashboard/estoque.php. Recebe JSON: { id, csrf_token }.
 *
 * Desfaz o efeito da movimentação na Quantidade do produto antes de
 * apagá-la (subtrai se era entrada, soma se era saída) — ver a doc de
 * app/Entity/MovimentacaoEstoque.php.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\MovimentacaoEstoque;
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
    echo json_encode(['erro' => 'Movimentação inválida.']);
    exit;
}

$movimentacao = MovimentacaoEstoque::getMovimentacao((int) $dados['id']);

if (!$movimentacao instanceof MovimentacaoEstoque) {
    http_response_code(422);
    echo json_encode(['erro' => 'Movimentação não encontrada.']);
    exit;
}

$produto = Produto::getProduto($movimentacao->ProdutoId);

if ($produto instanceof Produto) {
    $novaQuantidade = $movimentacao->Tipo === 'entrada'
        ? $produto->Quantidade - $movimentacao->Quantidade
        : $produto->Quantidade + $movimentacao->Quantidade;

    if ($novaQuantidade < 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Não é possível desfazer: o estoque atual de '.$produto->Nome.' é menor que essa entrada.']);
        exit;
    }

    $produto->Quantidade = $novaQuantidade;
    $produto->atualizar();
}

$movimentacaoParaExcluir = new MovimentacaoEstoque();
$movimentacaoParaExcluir->id = $movimentacao->id;
$movimentacaoParaExcluir->excluir();

echo json_encode(['sucesso' => true]);
