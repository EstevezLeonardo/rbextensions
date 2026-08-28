<?php

/**
 * Endpoint POST que registra uma compra de produtos (tabela
 * `compras_produtos`) a partir do formulário "Registrar Compra" de
 * dashboard/estoque.php. Recebe JSON: { categoria, fornecedor, data,
 * valorTotal, parcelas, csrf_token }.
 *
 * Uma compra em N parcelas (parcelas > 1) não é uma linha só: gera N
 * linhas na tabela, uma por mês, todas no mesmo dia da compra (a data
 * enviada é a da 1ª parcela; a 2ª cai um mês depois, e assim por
 * diante), cada uma já com ParcelaAtual/ParcelaTotal corretos (ex: 1/3,
 * 2/3, 3/3). ValorTotal é o valor total da compra, repetido em todas as
 * N linhas — "Valor da Parcela" (ValorTotal ÷ ParcelaTotal) é calculado
 * na hora de exibir a lista (dashboard/src/estoque.ts), não fica
 * guardado no banco.
 *
 * Se o dia da compra não existir num mês seguinte (ex: compra no dia
 * 31 e o mês seguinte só tem 30 dias), a parcela cai no último dia
 * daquele mês.
 *
 * Só registro financeiro — não identifica produto/quantidade
 * específicos, então não mexe em Produto::Quantidade nem gera
 * movimentação de estoque (ver a doc de app/Entity/CompraProduto.php).
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

if (empty($dados['categoria'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Selecione a categoria do produto.']);
    exit;
}

if (empty($dados['fornecedor'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Informe o fornecedor.']);
    exit;
}

if (empty($dados['data'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Informe a data e hora da compra.']);
    exit;
}

if (!isset($dados['valorTotal']) || !is_numeric($dados['valorTotal']) || $dados['valorTotal'] <= 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'Valor total inválido.']);
    exit;
}

$parcelas = isset($dados['parcelas']) && is_numeric($dados['parcelas']) ? (int) $dados['parcelas'] : 1;

if ($parcelas < 1 || $parcelas > 12) {
    http_response_code(422);
    echo json_encode(['erro' => 'Número de parcelas inválido (de 1 a 12).']);
    exit;
}

/**
 * Soma $meses meses a $dataHora ("AAAA-MM-DD HH:MM:SS"), mantendo o dia
 * do mês — ou o último dia do mês de destino, se ele não tiver dias
 * suficientes (ex: dia 31 + 1 mês, num mês de 30 dias).
 */
function somarMeses(string $dataHora, int $meses): string {
    $data = new DateTime($dataHora);
    $dia = (int) $data->format('j');

    $data->modify('first day of this month');
    $data->modify('+'.$meses.' months');

    $ultimoDiaDoMesDestino = (int) $data->format('t');
    $data->setDate((int) $data->format('Y'), (int) $data->format('n'), min($dia, $ultimoDiaDoMesDestino));

    return $data->format('Y-m-d H:i:s');
}

// o <input type="datetime-local"> manda "AAAA-MM-DDTHH:MM"; o MySQL
// espera "AAAA-MM-DD HH:MM:SS" (mesma conversão de dashboard/eventos-criar.php)
$dataBase = str_replace('T', ' ', $dados['data']);

$compras = [];
for ($numeroParcela = 1; $numeroParcela <= $parcelas; $numeroParcela++) {
    $compra = new CompraProduto();
    $compra->Categoria = $dados['categoria'];
    $compra->Fornecedor = $dados['fornecedor'];
    $compra->Data = somarMeses($dataBase, $numeroParcela - 1);
    $compra->ValorTotal = $dados['valorTotal'];
    $compra->ParcelaAtual = $numeroParcela;
    $compra->ParcelaTotal = $parcelas;
    $compra->cadastrar();

    $compras[] = $compra;
}

echo json_encode($compras);
