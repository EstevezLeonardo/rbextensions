<?php

/**
 * Dashboard (painel principal pós-login). Exige login, verificado
 * via a mesma camada de sessão do restante do projeto (require
 * relativo ao vendor/autoload.php do próprio rbextensions).
 *
 * O botão de barras (☰) abre um menu com as visões do painel — cada
 * uma é um elemento .visao-conteudo com data-visao correspondente ao
 * data-visao do botão da opção (ver dashboard/src/index.ts):
 *   - "logistico-financeiro" (padrão): os 3 cards, todos referentes a
 *     HOJE — "Compras Finalizadas" (nº de vendas concluídas hoje),
 *     "Balanço de Produtos" (entradas menos saídas de estoque hoje,
 *     App\Entity\MovimentacaoEstoque::getBalancoDoDia) e "Valor Total
 *     das Vendas" (soma bruta do dia, App\Entity\Venda::getResumoFinanceiro);
 *   - "extrato-vendas": as 5 vendas mais recentes (App\Entity\Venda),
 *     cujo botão "Venda" de cada linha vai pro extrato completo
 *     daquela venda em dashboard/vendas.php (?venda_id=); logo abaixo,
 *     o gráfico (doughnut) de vendas por mês do ano atual
 *     (Venda::getTotalPorMes) — só existe/é montado nessa visão (ver
 *     dashboard/src/index.ts), não nos 3 cards.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Entity\Venda;
use App\Entity\MovimentacaoEstoque;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();
$ultimasVendas = Venda::getVendas(null, [], 5);
$vendasPorMes = Venda::getTotalPorMes();

$vendasConcluidasHoje = Venda::getTotalVendas("DATE(v.Data) = CURDATE() AND v.Status = 'concluida'");
$balancoDeProdutosHoje = MovimentacaoEstoque::getBalancoDoDia();
$resumoFinanceiroHoje = Venda::getResumoFinanceiro('DATE(v.Data) = CURDATE()');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard RB</title>
    <link rel="stylesheet" href="public/assets/css/all.css">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css?v=<?= filemtime(__DIR__.'/public/assets/css/style.css') ?>">
</head>
<body>
            
    <div class="container-fluid">
        <header class="head">
            
            <nav>
                <div class="logo">
                    <a href="index.php">
                        <img src="public/assets/images/Royal_Brazilian_Extensions_logo_transparente.png" alt="Logo">
                    </a> 

                </div>
                <form action="" class="form-group">
                    <div class="rows">
                        <input type="text" name="search" class="form-control rounded-0" placeholder="Pesquisar opções">
                        <i class="fa-solid fa-search"></i>
                    </div>
                </form>
                
                    <ul>
                        <li><a href="index.php" class="actives"><span><i class="fa-solid fa-home"></i></span>Home</a></li>
                        <li><a href="agenda.php"><span><i class="fa-solid fa-calendar-alt"></i></span>Agenda</a></li>
                        <li><a href="perfil.php"><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
                        <li><a href="controle-produtos.php"><span><i class="fa-solid fa-box"></i></span>Produtos</a></li>
                        <li><a href="servicos.php"><span><i class="fa-solid fa-envelope"></i></span>Correio</a></li>
                        <li><a href="../usuarios/listar.php"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
                        <li><a href="vendas.php"><span><i class="fa-solid fa-shopping-cart"></i></span>Vendas</a></li>     
                        <li><a href="estoque.php"><span><i class="fa-solid fa-warehouse"></i></span>Estoque</a></li>
                        <li><a href="financeiro.php"><span><i class="fa-solid fa-dollar"></i></span>Financeiro</a></li>
                    </ul>

            </nav>

        </header>
        <main>
            <div class="nav-top">
                <div class="bars">
                    <button type="button" class="btns" id="botao-selecionar-visao" aria-haspopup="true" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
                    <p class="marca-painel">Painel <span>RB</span></p>

                    <div class="menu-visoes escondido" id="menu-visoes">
                        <button type="button" class="menu-visoes-opcao ativo" data-visao="logistico-financeiro">Controle Logístico e Financeiro</button>
                        <button type="button" class="menu-visoes-opcao" data-visao="extrato-vendas">Extratos de Vendas</button>
                    </div>
                </div>
                    <div class="user-notification">
                        <a href="perfil.php" title="Ver perfil">
                            <button class="users">
                                <p>Olá, <span><?= htmlspecialchars($usuarioLogado->nome, ENT_QUOTES, 'UTF-8') ?></span></p>
                                <i class="fa-solid fa-user"></i>
                            </button>
                        </a>
                        <a href="servicos.php" title="Ver notificações">
                            <button class="notification">
                                <i class="fa-solid fa-bell"></i>
                                <span id="notificacoes-badge" class="escondido">0</span>
                            </button>
                        </a>
                        <a href="../usuarios/logout.php" title="Sair">
                            <button class="notification logout">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </a>
                    </div>
                
            </div> 
            <div class="container-fluids">
                <div class="cards visao-conteudo" data-visao="logistico-financeiro">
                    <div class="cards-header">
                        <div class="cards-top">
                                <div class="card-number">
                                    <p class="value-total"><?= (int) $vendasConcluidasHoje ?></p>
                                    <p class="days">Vendas hoje</p>
                                </div>

                                <a href="vendas.php" title="Ir para Vendas">
                                    <div class="cards-icons">
                                        <i class="fa-solid fa-shopping-cart"></i>
                                    </div>
                                </a>
                            </div>

                        <div class="cards-type">
                            <p class="ticket">
                                Compras Finalizadas
                            </p>
                        </div>
                    </div>

                    <div class="cards-header">
                        <div class="cards-top">
                                <div class="card-number">
                                    <p class="value-total valor-entradas-saidas">
                                        <span class="valor-entrada"><i class="fa-solid fa-arrow-up"></i> <?= (int) $balancoDeProdutosHoje->entradas ?></span>
                                        <span class="valor-saida"><i class="fa-solid fa-arrow-down"></i> <?= (int) $balancoDeProdutosHoje->saidas ?></span>
                                    </p>
                                    <p class="days">Entradas/Saídas hoje</p>
                                </div>

                                <a href="estoque.php" title="Ir para Estoque">
                                    <div class="cards-icons">
                                        <i class="fa-solid fa-warehouse"></i>
                                    </div>
                                </a>
                            </div>

                        <div class="cards-type">
                            <p class="ticket">
                                Balanço de Produtos
                            </p>
                        </div>
                    </div>

                    <div class="cards-header">
                        <div class="cards-top">
                                <div class="card-number">
                                    <p class="value-total valor-moeda">R$ <?= number_format((float) $resumoFinanceiroHoje->total, 2, ',', '.') ?></p>
                                    <p class="days">Valor</p>
                                </div>

                                <a href="financeiro.php" title="Ir para Financeiro">
                                    <div class="cards-icons">
                                        <i class="fa-solid fa-dollar"></i>
                                    </div>
                                </a>
                            </div>

                        <div class="cards-type">
                            <p class="ticket">
                                Valor Total das Vendas
                            </p>
                        </div>
                    </div>
                </div> 
                <div class="containers-fluid">
                    <div class="rows-responsive visao-conteudo escondido" data-visao="extrato-vendas">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ultimasVendas)): ?>
                                    <tr>
                                        <td colspan="3">Nenhuma venda registrada ainda.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($ultimasVendas as $venda): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($venda->ClienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>R$ <?= number_format((float) $venda->ValorTotal, 2, ',', '.') ?></td>
                                        <td><a class="btn btn-sm" href="vendas.php?venda_id=<?= (int) $venda->id ?>">Venda</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="charts-products visao-conteudo escondido"
                        data-visao="extrato-vendas"
                        data-vendas-por-mes="<?= htmlspecialchars(json_encode(array_values($vendasPorMes)), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <canvas id="myChart"></canvas>
                    </div>

                </div>
            </div>
        </main>
        </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/notificacoes.js?v=<?= filemtime(__DIR__.'/public/assets/js/notificacoes.js') ?>"></script>
    <script src="public/assets/js/busca-menu.js?v=<?= filemtime(__DIR__.'/public/assets/js/busca-menu.js') ?>"></script>
    <script src="public/assets/js/chart.js"></script>
    <script src="public/assets/js/index.js?v=<?= filemtime(__DIR__.'/public/assets/js/index.js') ?>"></script>
</body>
</html>