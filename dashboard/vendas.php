<?php

/**
 * Vendas: mostra o extrato de vendas — uma linha por item vendido, com
 * cliente, produto, quantidade, data/hora, valor do produto e valor
 * total da compra (tabelas `vendas` + `venda_itens`, ver
 * app/Entity/Venda.php e VendaItem.php).
 *
 * Só gestão/consulta (extrato + busca) — não registra venda nova por
 * aqui. Isso vai ficar a cargo do futuro site de vendas voltado ao
 * cliente, que pode reaproveitar dashboard/vendas-criar.php (endpoint
 * mantido, mesmo sem UI própria nesta página) pra gravar a venda e
 * descontar o estoque.
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
    <title>Vendas RB</title>
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
                        <li><a href="servicos.php"><span><i class="fa-solid fa-concierge-bell"></i></span>Serviços</a></li>
                        <li><a href="../usuarios/listar.php"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
                        <li><a href="vendas.php" class="actives"><span><i class="fa-solid fa-shopping-cart"></i></span>Vendas</a></li>
                        <li><a href="estoque.php"><span><i class="fa-solid fa-warehouse"></i></span>Estoque</a></li>
                        <li><a href="financeiro.php"><span><i class="fa-solid fa-dollar"></i></span>Financeiro</a></li>
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
                Extrato de vendas: uma linha por item vendido (mesmos
                dados de dashboard/vendas-listar.php), mais recente
                primeiro. "Valor Total" é o total da COMPRA inteira
                (repete pra cada item de uma mesma venda), não só desse
                item — ver app/Entity/VendaItem.php.
            -->
            <div class="cards">
                <div class="event-form-card event-list-card">
                    <h3>Extrato de Vendas</h3>
                    <p id="lista-vendas-mensagem" class="evento-mensagem"></p>

                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Código do Produto</th>
                                    <th>Quantidade</th>
                                    <th>Data e Hora</th>
                                    <th>Valor do Produto</th>
                                    <th>Valor Total da Compra</th>
                                </tr>
                            </thead>
                            <tbody id="lista-vendas"></tbody>
                        </table>
                    </div>

                    <div id="paginacao-vendas" class="paginacao"></div>
                </div>
            </div>

            <!--
                "Buscar no Extrato" filtra a tabela acima
                (dashboard/vendas-listar.php) por cliente ou produto.
                Toda a lógica está em dashboard/public/assets/js/vendas.js.
            -->
            <div class="cards">
                <div class="event-form-card">
                    <h3>Buscar no Extrato</h3>
                    <div class="campo">
                        <label for="busca-venda-texto">Cliente ou produto</label>
                        <input type="text" id="busca-venda-texto" placeholder="Buscar por cliente, nome ou código do produto">
                    </div>
                    <button type="button" id="botao-buscar-vendas">Buscar</button>
                </div>
            </div>
        </main>
        </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/vendas.js"></script>
</body>
</html>
