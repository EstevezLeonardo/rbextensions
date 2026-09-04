<?php

/**
 * Agenda (calendário de eventos). Exige login, mesma checagem de
 * sessão usada no restante do projeto.
 *
 * Estrutura copiada de dashboard/index.php, sem a div.container-fluids
 * (cards/tabela/gráfico de vendas), com uma div#calendar no lugar. O
 * calendário em si é montado pelo FullCalendar (scripts vendorizados
 * em public/assets/js/fullcalendar/) via dashboard/public/assets/js/agenda.js
 * — esse .js é compilado a partir de dashboard/src/agenda.ts pelo
 * TypeScript (rode `npm run build` na raiz do projeto após editar o
 * .ts). Os eventos vêm da tabela `eventos` (id, Titulo, Inicio, Fim)
 * através do endpoint dashboard/eventos.php, em JSON.
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
    <title>Agenda RB</title>
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
                        <li><a href="index.php" class="actives"><span><i class="fa-solid fa-home"></i></span>Home</a></li>
                        <li><a href="agenda.php"><span><i class="fa-solid fa-calendar-alt"></i></span>Agenda</a></li>
                        <li><a href=""><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
                        <li><a href="controle-produtos.php"><span><i class="fa-solid fa-box"></i></span>Produtos</a></li>
                        <li><a href="servicos.php"><span><i class="fa-solid fa-concierge-bell"></i></span>Serviços</a></li>
                        <li><a href="../usuarios/listar.php"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
                        <li><a href="vendas.php"><span><i class="fa-solid fa-shopping-cart"></i></span>Vendas</a></li>
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
                Cores do calendário aplicadas via variáveis CSS do
                FullCalendar (ver .calendar-wrapper em style.css). O
                FullCalendar é montado aqui dentro por
                dashboard/public/assets/js/agenda.js.
            -->
            <div class="calendar-wrapper">
                <div id="calendar"></div>
            </div>

            <!--
                "Adicionar Evento" grava na tabela `eventos` via
                dashboard/eventos-criar.php — o mesmo formulário vira um
                formulário de EDIÇÃO (dashboard/eventos-editar.php)
                quando a pessoa clica em "Editar" num item da lista de
                resultados (o #evento-id escondido guarda qual evento
                está sendo editado; vazio = modo "criar"). "Buscar
                Eventos" filtra tanto o calendário quanto a lista de
                resultados (dashboard/eventos.php) por título e por
                status (futuro/encerrado). Toda a lógica está em
                dashboard/public/assets/js/agenda.js.
            -->
            <div class="cards">
                <div class="event-form-card">
                    <h3 id="evento-form-titulo">Adicionar Evento</h3>
                    <p id="evento-mensagem" class="evento-mensagem"></p>
                    <form id="form-evento">
                        <!-- token CSRF: confirmado em eventos-criar.php/eventos-editar.php antes de gravar -->
                        <input type="hidden" id="evento-csrf-token" value="<?= \App\Session\Csrf::token() ?>">
                        <!-- vazio = criando um evento novo; preenchido = editando o evento com esse id -->
                        <input type="hidden" id="evento-id" value="">

                        <div class="campo">
                            <label for="evento-titulo">Título</label>
                            <input type="text" id="evento-titulo" name="titulo" required>
                        </div>
                        <div class="campo">
                            <label for="evento-inicio">Início</label>
                            <input type="datetime-local" id="evento-inicio" name="inicio" required>
                        </div>
                        <div class="campo">
                            <label for="evento-fim">Fim</label>
                            <input type="datetime-local" id="evento-fim" name="fim" required>
                        </div>
                        <button type="submit" id="botao-evento-submit">Adicionar Evento</button>
                        <button type="button" id="botao-cancelar-edicao" class="escondido">Cancelar</button>
                    </form>
                </div>

                <div class="event-form-card">
                    <h3>Buscar Eventos</h3>
                    <div class="campo">
                        <label for="busca-titulo">Título</label>
                        <input type="text" id="busca-titulo" name="busca_titulo" placeholder="Buscar por título">
                    </div>
                    <div class="campo">
                        <label for="busca-status">Status</label>
                        <select id="busca-status" name="busca_status">
                            <option value="">Todos</option>
                            <option value="futuro">Futuro</option>
                            <option value="encerrado">Já encerrado</option>
                        </select>
                    </div>
                    <button type="button" id="botao-buscar-eventos">Buscar</button>
                </div>
            </div>

            <!--
                Lista dos eventos que batem com a busca acima, em ordem
                (mesmos dados de dashboard/eventos.php). Cada item tem
                os botões "Editar" (carrega o evento no formulário
                acima) e "Excluir" (pede confirmação clicando duas
                vezes, para não apagar por engano).
            -->
            <div class="cards">
                <div class="event-form-card event-list-card">
                    <h3>Resultados da Busca</h3>
                    <p id="lista-eventos-mensagem" class="evento-mensagem"></p>
                    <ol id="lista-eventos" class="lista-eventos"></ol>
                </div>
            </div>
        </main>
        </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>

    <!-- FullCalendar (build "global", sem módulos/bundler) + nosso script da agenda -->
    <script src="public/assets/js/fullcalendar/fullcalendar-core.min.js"></script>
    <script src="public/assets/js/fullcalendar/fullcalendar-daygrid.min.js"></script>
    <script src="public/assets/js/agenda.js"></script>
</body>
</html>
