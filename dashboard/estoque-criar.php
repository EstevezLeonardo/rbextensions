<?php

/**
 * Endpoint POST que registra uma movimentação de estoque (tabela
 * `movimentacoes_estoque`) a partir do formulário "Registrar
 * Movimentação" de dashboard/estoque.php. Recebe JSON: { produtoId,
 * tipo, quantidade, observacao, csrf_token }.
 *
 * Também ajusta a Quantidade do produto (soma na entrada, subtrai na
 * saída) — não é responsabilidade da entidade MovimentacaoEstoque, ver
 * a doc de app/Entity/MovimentacaoEstoque.php.
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

if (empty($dados['produtoId']) || !is_numeric($dados['produtoId'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Selecione um produto.']);
    exit;
}

if (($dados['tipo'] ?? '') !== 'entrada' && ($dados['tipo'] ?? '') !== 'saida') {
    http_response_code(422);
    echo json_encode(['erro' => 'Selecione o tipo da movimentação (entrada ou saída).']);
    exit;
}

if (!isset($dados['quantidade']) || !is_numeric($dados['quantidade']) || $dados['quantidade'] <= 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'Quantidade inválida.']);
    exit;
}

$produto = Produto::getProduto((int) $dados['produtoId']);

if (!$produto instanceof Produto) {
    http_response_code(422);
    echo json_encode(['erro' => 'Produto não encontrado.']);
    exit;
}

$tipo = $dados['tipo'];
$quantidade = (int) $dados['quantidade'];

if ($tipo === 'saida' && $quantidade > $produto->Quantidade) {
    http_response_code(422);
    echo json_encode(['erro' => 'Estoque insuficiente: só há '.$produto->Quantidade.' unidade(s) de '.$produto->Nome.'.']);
    exit;
}

$produto->Quantidade = $tipo === 'entrada' ? $produto->Quantidade + $quantidade : $produto->Quantidade - $quantidade;
$produto->atualizar();

$movimentacao = new MovimentacaoEstoque();
$movimentacao->ProdutoId = $produto->id;
$movimentacao->Tipo = $tipo;
$movimentacao->Quantidade = $quantidade;
$movimentacao->Observacao = $dados['observacao'] ?? '';
$movimentacao->Data = date('Y-m-d H:i:s');
$movimentacao->cadastrar();

echo json_encode(MovimentacaoEstoque::getMovimentacao($movimentacao->id));
