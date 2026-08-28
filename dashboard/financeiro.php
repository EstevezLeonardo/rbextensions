<?php

/**
 * Financeiro: resumo dos valores movimentados nas vendas (tabela
 * `vendas`) num período — valor total das vendas, valores extornados,
 * valores pagos em débito/PIX e valores pagos em cartão de crédito
 * (ver App\Entity\Venda::getResumoFinanceiro) — mais a lista de vendas
 * do período, com a opção de marcar uma venda como extornada.
 *
 * O filtro de período (De/Até) vale tanto pro resumo quanto pra lista
 * de vendas abaixo; toda a lógica de tela está em
 * dashboard/public/assets/js/financeiro.js (compilado de
 * dashboard/src/financeiro.ts).
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro RB</title>
    <link rel="stylesheet" href="public/assets/css/all.css">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css?v=<?= filemtime(__DIR__.'/public/assets/css/style.css') ?>">
</head>
<body>

    <div class="container-fluid">
        <header class="head">

            <nav>
                <div class="logo">
                    <a href="#">
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
                        <li><a href="index.php"><span><i class="fa-solid fa-home"></i></span>Home</a></li>
                        <li><a href="agenda.php"><span><i class="fa-solid fa-calendar-alt"></i></span>Agenda</a></li>
                        <li><a href=""><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
                        <li><a href="controle-produtos.php"><span><i class="fa-solid fa-box"></i></span>Produtos</a></li>
                        <li><a href=""><span><i class="fa-solid fa-concierge-bell"></i></span>Serviços</a></li>
                        <li><a href="../usuarios/listar.php"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
                        <li><a href="vendas.php"><span><i class="fa-solid fa-shopping-cart"></i></span>Vendas</a></li>
                        <li><a href="estoque.php"><span><i class="fa-solid fa-warehouse"></i></span>Estoque</a></li>
                        <li><a href="financeiro.php" class="actives"><span><i class="fa-solid fa-dollar"></i></span>Financeiro</a></li>
                    </ul>

            </nav>

        </header>
        <main>
            <div class="nav-top">
                    <div class="user-notification">
                        <button
                        class="users">
                            <p>Olá, <span><?= htmlspecialchars($usuarioLogado->nome, ENT_QUOTES, 'UTF-8') ?></span></p>
                            <i class="fa-solid fa-user"></i>
                        </button>
                        <button class="notification">
                            <i class="fa-solid fa-bell"></i>
                            <span>1</span>
                        </button>
                        <a href="../usuarios/logout.php" title="Sair">
                            <button class="notification logout">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </a>
                    </div>

            </div>

            <!--
                Resumo financeiro do período selecionado (mesmos dados
                de dashboard/financeiro-resumo.php). "Valor Total das
                Vendas" é bruto (todas as vendas do período); "Valores
                Extornados" já está incluído nesse bruto, mostrado à
                parte pra transparência — os totais de Débito/PIX e
                Cartão de Crédito NÃO contam as extornadas (ver
                App\Entity\Venda::getResumoFinanceiro).
            -->
            <div class="cards">
                <div class="event-form-card event-list-card largura-reduzida">
                    <h3>Resumo Financeiro</h3>

                    <div class="campo">
                        <label for="financeiro-de">De</label>
                        <input type="date" id="financeiro-de">
                    </div>
                    <div class="campo">
                        <label for="financeiro-ate">Até</label>
                        <input type="date" id="financeiro-ate">
                    </div>
                    <button type="button" id="botao-filtrar-financeiro">Filtrar</button>

                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Valor Total das Vendas</th>
                                    <th>Valores Extornados</th>
                                    <th>Valores Pagos em Débito/PIX</th>
                                    <th>Valores Pagos em Cartão de Crédito</th>
                                    <th>Saída de Valores</th>
                                </tr>
                            </thead>
                            <tbody id="resumo-financeiro"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!--
                Lista das vendas do mesmo período do resumo acima
                (dashboard/financeiro-vendas-listar.php), com o botão
                "Marcar como Estornada" por linha (some quando a venda
                já está extornada).
            -->
            <div class="cards">
                <div class="event-form-card event-list-card largura-reduzida">
                    <h3>Vendas do Período</h3>
                    <p id="lista-financeiro-vendas-mensagem" class="evento-mensagem"></p>

                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Data e Hora</th>
                                    <th>Valor Total</th>
                                    <th>Forma de Pagamento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-financeiro-vendas"></tbody>
                        </table>
                    </div>

                    <div id="paginacao-financeiro-vendas" class="paginacao"></div>
                </div>
            </div>

            <input type="hidden" id="financeiro-csrf-token" value="<?= \App\Session\Csrf::token() ?>">
        </main>
        </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/financeiro.js"></script>
</body>
</html>
