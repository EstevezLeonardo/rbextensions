<?php

/**
 * Endpoint JSON: devolve o resumo financeiro do período pedido — valor
 * total das vendas, valores extornados, valores pagos em débito/PIX e
 * valores pagos em cartão de crédito (tabela `vendas`, ver
 * App\Entity\Venda::getResumoFinanceiro), mais a saída de valores
 * (tabela `compras_produtos`, ver App\Entity\CompraProduto::getTotalGasto —
 * quanto foi gasto comprando produtos pra repor o estoque). Consumido
 * por dashboard/financeiro.php (ver dashboard/src/financeiro.ts).
 *
 * Aceita via querystring, vindos do filtro de período:
 *   - de:  data inicial (AAAA-MM-DD), inclusive
 *   - ate: data final (AAAA-MM-DD), inclusive
 * Sem os dois, soma todas as vendas/compras já registradas.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Venda;
use App\Entity\CompraProduto;
use App\Session\Login;

Login::requireLogin();

$de = trim($_GET['de'] ?? '');
$ate = trim($_GET['ate'] ?? '');

// filtro de vendas (colunas prefixadas com "v.", ver Venda::getResumoFinanceiro)
$condicoesVendas = [];
$paramsVendas = [];

if ($de !== '') {
    $condicoesVendas[] = 'v.Data >= ?';
    $paramsVendas[] = $de.' 00:00:00';
}

if ($ate !== '') {
    $condicoesVendas[] = 'v.Data <= ?';
    $paramsVendas[] = $ate.' 23:59:59';
}

// filtro de compras (tabela própria, sem prefixo — ver CompraProduto::getTotalGasto)
$condicoesCompras = [];
$paramsCompras = [];

if ($de !== '') {
    $condicoesCompras[] = 'Data >= ?';
    $paramsCompras[] = $de.' 00:00:00';
}

if ($ate !== '') {
    $condicoesCompras[] = 'Data <= ?';
    $paramsCompras[] = $ate.' 23:59:59';
}

$resumo = Venda::getResumoFinanceiro(implode(' AND ', $condicoesVendas), $paramsVendas);
$resumo->saida = CompraProduto::getTotalGasto(implode(' AND ', $condicoesCompras), $paramsCompras);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($resumo);
