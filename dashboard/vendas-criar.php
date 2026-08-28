<?php

/**
 * Endpoint POST que registra uma venda (tabelas `vendas` +
 * `venda_itens`). Recebe JSON: { clienteId, formaPagamento
 * [debito/pix/credito], itens: [{ produtoId, quantidade }, ...],
 * csrf_token }.
 *
 * Sem UI própria em dashboard/vendas.php (que hoje só mostra o
 * extrato) — pensado pra ser chamado pelo futuro site de vendas.
 *
 * Cada item desconta a Quantidade do produto correspondente e gera uma
 * saída no histórico de Estoque (mesmo efeito de registrar a saída
 * manualmente em dashboard/estoque.php), pra manter tudo consistente.
 *
 * Valida o carrinho inteiro (cliente, produtos e estoque disponível de
 * cada item) ANTES de gravar qualquer coisa, pra nunca deixar uma
 * venda pela metade — mesma cautela dos demais endpoints deste
 * projeto, que também não usam transação de banco (ver
 * app/Db/Database.php).
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Venda;
use App\Entity\VendaItem;
use App\Entity\Produto;
use App\Entity\Vaga;
use App\Entity\MovimentacaoEstoque;
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

if (empty($dados['clienteId']) || !is_numeric($dados['clienteId'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Selecione o cliente.']);
    exit;
}

$cliente = Vaga::getVaga((int) $dados['clienteId']);

if (!$cliente instanceof Vaga) {
    http_response_code(422);
    echo json_encode(['erro' => 'Cliente não encontrado.']);
    exit;
}

if (!in_array($dados['formaPagamento'] ?? '', ['debito', 'pix', 'credito'], true)) {
    http_response_code(422);
    echo json_encode(['erro' => 'Selecione a forma de pagamento (débito, PIX ou crédito).']);
    exit;
}

if (empty($dados['itens']) || !is_array($dados['itens'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Adicione ao menos um produto à venda.']);
    exit;
}

// primeira passada: valida cada item (produto existe, quantidade válida,
// estoque suficiente) e já monta a lista de produtos carregados, sem
// gravar nada ainda — se algum item falhar, a venda inteira é recusada.
// $reservadoPorProduto acumula quanto já foi "prometido" a cada produto
// nesta mesma venda, pra um produto repetido em dois itens do carrinho
// não passar da validação de estoque contando o mesmo saldo duas vezes.
$itensValidados = [];
$valorTotal = 0;
$reservadoPorProduto = [];

foreach ($dados['itens'] as $item) {
    if (empty($item['produtoId']) || !is_numeric($item['produtoId'])) {
        http_response_code(422);
        echo json_encode(['erro' => 'Item de venda inválido.']);
        exit;
    }

    if (!isset($item['quantidade']) || !is_numeric($item['quantidade']) || $item['quantidade'] <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Quantidade inválida em um dos itens.']);
        exit;
    }

    $produto = Produto::getProduto((int) $item['produtoId']);

    if (!$produto instanceof Produto) {
        http_response_code(422);
        echo json_encode(['erro' => 'Um dos produtos da venda não foi encontrado.']);
        exit;
    }

    $quantidade = (int) $item['quantidade'];
    $jaReservado = $reservadoPorProduto[$produto->id] ?? 0;

    if ($jaReservado + $quantidade > $produto->Quantidade) {
        http_response_code(422);
        echo json_encode(['erro' => 'Estoque insuficiente: só há '.$produto->Quantidade.' unidade(s) de '.$produto->Nome.'.']);
        exit;
    }

    $reservadoPorProduto[$produto->id] = $jaReservado + $quantidade;
    $itensValidados[] = ['produto' => $produto, 'quantidade' => $quantidade];
    $valorTotal += $produto->Preco * $quantidade;
}

$venda = new Venda();
$venda->ClienteId = $cliente->id;
$venda->Data = date('Y-m-d H:i:s');
$venda->ValorTotal = $valorTotal;
$venda->FormaPagamento = $dados['formaPagamento'];
$venda->cadastrar();

foreach ($itensValidados as $itemValidado) {
    $produto = $itemValidado['produto'];
    $quantidade = $itemValidado['quantidade'];

    $item = new VendaItem();
    $item->VendaId = $venda->id;
    $item->ProdutoId = $produto->id;
    $item->Quantidade = $quantidade;
    $item->ValorUnitario = $produto->Preco;
    $item->cadastrar();

    $produto->Quantidade -= $quantidade;
    $produto->atualizar();

    $movimentacao = new MovimentacaoEstoque();
    $movimentacao->ProdutoId = $produto->id;
    $movimentacao->Tipo = 'saida';
    $movimentacao->Quantidade = $quantidade;
    $movimentacao->Observacao = 'Venda #'.$venda->id.' — '.$cliente->nome.' '.$cliente->sobrenome;
    $movimentacao->Data = $venda->Data;
    $movimentacao->cadastrar();
}

echo json_encode([
    'id' => $venda->id,
    'valorTotal' => $venda->ValorTotal,
]);
