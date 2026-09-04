<?php

/**
 * Serviços: envio de e-mail e caixa de entrada via API do Gmail
 * (OAuth2 — a pessoa loga com a própria conta Google, sem digitar
 * senha nenhuma aqui), usando sempre a conta do usuário logado nesta
 * sessão — nunca uma conta fixa/compartilhada. Exige login, mesma
 * checagem de sessão usada no restante do projeto.
 *
 * Estrutura copiada de dashboard/controle-produtos.php (mesmos cards
 * de formulário/lista), trocando produto por e-mail. "Conectar
 * E-mail"/"Sair do E-mail" e o resultado do login OAuth (?email_erro=/
 * ?email_conectado=) vêm de dashboard/servicos-google-conectar.php e
 * servicos-google-callback.php; envio e leitura são
 * servicos-enviar.php, servicos-inbox-listar.php e
 * servicos-inbox-ler.php (todos via App\Mail\GoogleOAuth/GmailApi).
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();
$emailConfigurado = !empty($usuarioLogado->google_refresh_token);

$mensagensDeErroOAuth = [
    'cancelado' => 'Conexão cancelada.',
    'sessao' => 'Sessão expirada. Clique em "Conectar E-mail" e tente de novo.',
    'sem_refresh_token' => 'O Google não devolveu permissão de acesso contínuo. Tente conectar de novo.',
    'google' => 'Não foi possível conectar ao Google agora. Tente de novo em instantes.',
];
$erroOAuth = $mensagensDeErroOAuth[$_GET['email_erro'] ?? ''] ?? null;
$conectadoAgora = isset($_GET['email_conectado']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços RB</title>
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
                        <li><a href="servicos.php" class="actives"><span><i class="fa-solid fa-concierge-bell"></i></span>Serviços</a></li>
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
                "Enviar E-mail" (dashboard/servicos-enviar.php) e a
                caixa de entrada ficam lado a lado (mesmo .cards de
                controle-produtos.php, que já organiza os cards em
                linha e estica todos pra mesma altura).
            -->
            <div class="cards">
                <div class="event-form-card">
                    <h3>Enviar E-mail</h3>
                    <p id="email-envio-mensagem" class="evento-mensagem"></p>
                    <form id="form-email-envio">
                        <input type="hidden" id="email-envio-csrf-token" value="<?= \App\Session\Csrf::token() ?>">

                        <div class="campo">
                            <label for="email-destinatario">Para</label>
                            <input type="email" id="email-destinatario" name="destinatario" required>
                        </div>
                        <div class="campo">
                            <label for="email-assunto">Assunto</label>
                            <input type="text" id="email-assunto" name="assunto" required>
                        </div>
                        <div class="campo">
                            <label for="email-mensagem">Mensagem</label>
                            <textarea id="email-mensagem" name="mensagem" rows="6" required></textarea>
                        </div>

                        <div class="campo">
                            <input type="file" id="email-anexos-input" multiple hidden>
                            <ul id="email-anexos-lista" class="email-anexos-selecionados"></ul>
                        </div>

                        <div class="acoes-envio">
                            <button type="submit" id="botao-email-enviar">Enviar</button>
                            <button type="button" id="botao-email-anexar" class="btn-secundario">Anexar</button>
                        </div>
                    </form>
                </div>

                <!--
                    Caixa de entrada: o botão no canto superior alterna
                    entre "Conectar E-mail" (link de verdade — navega
                    pra tela de consentimento do Google,
                    dashboard/servicos-google-conectar.php — e volta
                    por servicos-google-callback.php) e "Sair do
                    E-mail" (revoga e apaga o token salvo,
                    dashboard/servicos-desconectar-email.php). O menu
                    de pastas e o filtro por data ficam numa coluna à
                    esquerda da lista (dashboard/servicos-inbox-listar.php);
                    "Ler" busca o corpo completo
                    (dashboard/servicos-inbox-ler.php) logo abaixo do
                    item, sem sair da lista.
                -->
                <div class="event-form-card inbox-card">
                    <div class="inbox-cabecalho">
                        <h3>Caixa de Entrada</h3>
                        <div class="inbox-conta-acoes">
                            <a href="servicos-google-conectar.php" id="botao-email-conectar" class="<?= $emailConfigurado ? 'escondido' : '' ?>">Conectar E-mail</a>
                            <button type="button" id="botao-email-sair" class="<?= $emailConfigurado ? '' : 'escondido' ?>">Sair do E-mail</button>
                        </div>
                    </div>

                    <input type="hidden" id="email-acoes-csrf-token" value="<?= \App\Session\Csrf::token() ?>">

                    <p id="inbox-mensagem" class="evento-mensagem<?= $erroOAuth ? ' erro' : ($conectadoAgora ? ' sucesso' : '') ?>"><?= htmlspecialchars($erroOAuth ?? ($conectadoAgora ? 'E-mail conectado!' : ''), ENT_QUOTES, 'UTF-8') ?></p>

                    <div class="inbox-corpo">
                        <aside class="inbox-menu">
                            <ul class="inbox-pastas">
                                <li><button type="button" class="inbox-pasta-botao ativo" data-pasta="caixa">Caixa de Entrada</button></li>
                                <li><button type="button" class="inbox-pasta-botao" data-pasta="enviados">Itens Enviados</button></li>
                                <li><button type="button" class="inbox-pasta-botao" data-pasta="rascunhos">Rascunhos</button></li>
                                <li><button type="button" class="inbox-pasta-botao" data-pasta="lixeira">Lixeira</button></li>
                            </ul>
                            <div class="inbox-busca">
                                <div class="campo">
                                    <label for="inbox-busca-texto">Buscar (remetente ou assunto)</label>
                                    <input type="text" id="inbox-busca-texto" placeholder="Nome, e-mail ou palavra-chave">
                                </div>
                            </div>
                            <div class="inbox-calendario">
                                <div class="campo">
                                    <label for="inbox-data-inicio">De</label>
                                    <input type="date" id="inbox-data-inicio">
                                </div>
                                <div class="campo">
                                    <label for="inbox-data-fim">Até</label>
                                    <input type="date" id="inbox-data-fim">
                                </div>
                                <button type="button" id="botao-inbox-filtrar">Filtrar</button>
                                <button type="button" id="botao-inbox-limpar-filtro">Limpar</button>
                            </div>
                        </aside>

                        <div class="inbox-lista-area">
                            <button type="button" id="botao-inbox-atualizar">Atualizar</button>
                            <ol id="lista-inbox" class="lista-eventos"></ol>
                            <div id="paginacao-inbox" class="paginacao"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/servicos.js?v=<?= filemtime(__DIR__.'/public/assets/js/servicos.js') ?>"></script>
</body>
</html>
