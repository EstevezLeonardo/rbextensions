<?php

/**
 * Endpoint JSON: busca global usada pela caixa "Pesquisar opções" do
 * cabeçalho (dashboard/src/busca-menu.ts, presente em toda página do
 * dashboard e de usuarios/) — produtos, clientes e vendas que batem
 * com o termo digitado, pra montar a lista suspensa de resultados.
 *
 * Cada resultado só traz o suficiente pra montar o link certo no
 * front: produto e cliente abrem a respectiva página já filtrada
 * (App\Entity\Produto/Vaga, via ?busca=, que essas páginas já
 * suportam); venda abre o extrato daquela venda específica
 * (dashboard/vendas.php?venda_id=, mesmo link do botão "Venda" de
 * dashboard/index.php).
 *
 * Aceita via querystring:
 *   - q: termo buscado (menos de 2 caracteres devolve tudo vazio, pra
 *        não sair buscando a cada letra digitada)
 *
 * Devolve { produtos, clientes, vendas } — até 5 de cada.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Entity\Produto;
use App\Entity\Vaga;
use App\Entity\Venda;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$termo = trim($_GET['q'] ?? '');

if (mb_strlen($termo) < 2) {
    echo json_encode(['produtos' => [], 'clientes' => [], 'vendas' => []]);
    exit;
}

$curinga = "%{$termo}%";

$produtos = Produto::getProdutos('Nome LIKE ? OR Codigo LIKE ?', [$curinga, $curinga], 5);

$clientes = Vaga::getVagas('nome LIKE ? OR sobrenome LIKE ?', null, 5, [$curinga, $curinga]);

$vendas = ctype_digit($termo)
    ? Venda::getVendas('v.id = ?', [$termo], 5)
    : Venda::getVendas("CONCAT(u.nome, ' ', u.sobrenome) LIKE ?", [$curinga], 5);

echo json_encode([
    'produtos' => array_map(function ($produto) {
        return ['id' => $produto->id, 'codigo' => $produto->Codigo, 'nome' => $produto->Nome];
    }, $produtos),
    'clientes' => array_map(function ($cliente) {
        // nome e sobrenome separados (não concatenados) porque usuarios/listar.php
        // busca em "nome LIKE ? OR sobrenome LIKE ?" — um só dos dois campos,
        // então o link (?busca=) usa só o nome, pra sempre bater com a busca de lá
        return ['id' => $cliente->id, 'nome' => $cliente->nome, 'sobrenome' => $cliente->sobrenome];
    }, $clientes),
    'vendas' => array_map(function ($venda) {
        return ['id' => $venda->id, 'cliente' => $venda->ClienteNome, 'valorTotal' => (float) $venda->ValorTotal];
    }, $vendas),
]);
