<?php

/**
 * Perfil: dados pessoais do usuário logado (nome, sobrenome, e-mail,
 * data de nascimento, foto) e troca de senha — sempre o próprio
 * usuário da sessão (App\Session\Login::getUsuario()), nunca outro id
 * via querystring (isso já existe em usuarios/editar.php, pra um
 * usuário com acesso de gestão de clientes editar outra pessoa; aqui
 * não tem campo de "ativo" nem seleção de id — só o dono da sessão).
 *
 * Dois cards, dois <form> independentes (ver dashboard/src/perfil.ts):
 * "Meus Dados" só fica editável depois de clicar "Editar Dados" (o
 * botão "Salvar Alterações" dele só aparece nesse momento — os campos
 * chegam desabilitados do servidor, então nem são enviados se alguém
 * tentar postar sem passar pelo JS) e "Trocar Senha" tem seu próprio
 * botão, sempre visível. Cada form manda um campo escondido
 * "formulario" (dados|senha) pra esta página saber qual dos dois foi
 * enviado e validar/salvar só aquele — processado aqui mesmo (sem
 * endpoint separado, como usuarios/editar.php), com redirect de volta
 * pra si mesma em caso de sucesso (?sucesso=dados|senha), pra evitar
 * reenvio ao atualizar a página.
 *
 * Foto de perfil: guardada em public/assets/uploads/perfil (nome
 * gerado — nunca o nome original do arquivo — e extensão conferida
 * pelo mime type real, não pelo que o navegador mandou, antes de
 * gravar); só o nome do arquivo fica em App\Entity\Vaga::$foto.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Session\Csrf;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();
$formulario = $_POST['formulario'] ?? null;

$erroDados = null;
$erroSenha = null;
$sucessoDados = ($_GET['sucesso'] ?? null) === 'dados';
$sucessoSenha = ($_GET['sucesso'] ?? null) === 'senha';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate($_POST['csrf_token'] ?? '')) {
    $mensagemSessaoExpirada = 'Sessão expirada. Recarregue a página e tente novamente.';
    if ($formulario === 'senha') {
        $erroSenha = $mensagemSessaoExpirada;
    } else {
        $erroDados = $mensagemSessaoExpirada;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $formulario === 'dados') {
    $nome = trim($_POST['nome'] ?? '');
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $datanascimento = trim($_POST['datanascimento'] ?? '');
    $removerFoto = isset($_POST['remover_foto']);

    $tiposDeFotoPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $novaFotoTmp = null;
    $novaFotoExtensao = null;

    if ($nome === '' || $sobrenome === '' || $datanascimento === '') {
        $erroDados = 'Preencha nome, sobrenome e data de nascimento.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erroDados = 'Informe um e-mail válido.';
    }

    if ($erroDados === null && !empty($_FILES['foto']['name']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $erroDados = 'Não foi possível enviar a foto. Tente novamente.';
        } elseif ($_FILES['foto']['size'] > 3 * 1024 * 1024) {
            $erroDados = 'A foto precisa ter até 3 MB.';
        } else {
            $tipoReal = mime_content_type($_FILES['foto']['tmp_name']);
            if (!isset($tiposDeFotoPermitidos[$tipoReal])) {
                $erroDados = 'A foto precisa ser uma imagem (JPG, PNG, GIF ou WEBP).';
            } else {
                $novaFotoTmp = $_FILES['foto']['tmp_name'];
                $novaFotoExtensao = $tiposDeFotoPermitidos[$tipoReal];
            }
        }
    }

    if ($erroDados === null) {
        $diretorioFotos = __DIR__.'/public/assets/uploads/perfil';
        $fotoAntiga = $usuarioLogado->foto;

        if ($novaFotoTmp !== null) {
            if (!is_dir($diretorioFotos)) {
                mkdir($diretorioFotos, 0755, true);
            }
            $novoNomeArquivo = 'usuario_'.$usuarioLogado->id.'_'.bin2hex(random_bytes(8)).'.'.$novaFotoExtensao;
            move_uploaded_file($novaFotoTmp, $diretorioFotos.'/'.$novoNomeArquivo);
            $usuarioLogado->foto = $novoNomeArquivo;
        } elseif ($removerFoto) {
            $usuarioLogado->foto = null;
        }

        $usuarioLogado->nome = $nome;
        $usuarioLogado->sobrenome = $sobrenome;
        $usuarioLogado->email = $email;
        $usuarioLogado->datanascimento = $datanascimento;
        $usuarioLogado->atualizar();

        if ($fotoAntiga && $fotoAntiga !== $usuarioLogado->foto) {
            @unlink($diretorioFotos.'/'.$fotoAntiga);
        }

        header('Location: perfil.php?sucesso=dados');
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $formulario === 'senha') {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $senhaNova = $_POST['senha_nova'] ?? '';
    $senhaConfirmar = $_POST['senha_confirmar'] ?? '';

    if ($senhaAtual === '' || $senhaNova === '' || $senhaConfirmar === '') {
        $erroSenha = 'Preencha a senha atual e a nova senha (nos dois campos).';
    } elseif (!password_verify($senhaAtual, $usuarioLogado->senha)) {
        $erroSenha = 'Senha atual incorreta.';
    } elseif (strlen($senhaNova) < 6) {
        $erroSenha = 'A nova senha precisa ter pelo menos 6 caracteres.';
    } elseif ($senhaNova !== $senhaConfirmar) {
        $erroSenha = 'A confirmação não bate com a nova senha.';
    }

    if ($erroSenha === null) {
        $usuarioLogado->senha = password_hash($senhaNova, PASSWORD_DEFAULT);
        $usuarioLogado->atualizar();

        header('Location: perfil.php?sucesso=senha');
        exit;
    }
}

/** "Meus Dados" só nasce editável se o próprio envio desse formulário falhou (senão os campos ficam travados até "Editar Dados", ver dashboard/src/perfil.ts). */
$modoEdicaoDeDados = $formulario === 'dados' && $erroDados !== null;

$dataNascimentoParaExibir = $modoEdicaoDeDados
    ? ($_POST['datanascimento'] ?? '')
    : ($usuarioLogado->datanascimento ? date('Y-m-d', strtotime($usuarioLogado->datanascimento)) : '');
$fotoUrl = $usuarioLogado->foto ? 'public/assets/uploads/perfil/'.rawurlencode($usuarioLogado->foto) : null;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil RB</title>
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
                        <li><a href="index.php"><span><i class="fa-solid fa-home"></i></span>Home</a></li>
                        <li><a href="agenda.php"><span><i class="fa-solid fa-calendar-alt"></i></span>Agenda</a></li>
                        <li><a href="perfil.php" class="actives"><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
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

            <div class="cards">
                <form id="form-meus-dados" class="event-form-card perfil-dados-card" method="POST" enctype="multipart/form-data">
                    <h3>Meus Dados</h3>
                    <input type="hidden" name="formulario" value="dados">
                    <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                    <p class="evento-mensagem<?= $erroDados ? ' erro' : ($sucessoDados ? ' sucesso' : '') ?>"><?= htmlspecialchars($erroDados ?? ($sucessoDados ? 'Dados atualizados!' : ''), ENT_QUOTES, 'UTF-8') ?></p>

                    <div class="campo campo-foto">
                        <label>Foto de Perfil</label>
                        <div class="foto-perfil-atual">
                            <?php if ($fotoUrl): ?>
                                <img src="<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i>
                            <?php endif; ?>
                        </div>

                        <input type="file" name="foto" id="input-foto" class="campo-editavel" accept="image/png,image/jpeg,image/gif,image/webp" hidden <?= $modoEdicaoDeDados ? '' : 'disabled' ?>>
                        <input type="checkbox" name="remover_foto" id="input-remover-foto" value="1" class="campo-editavel" hidden <?= $modoEdicaoDeDados ? '' : 'disabled' ?>>

                        <div class="campo-foto-acoes">
                            <button type="button" id="botao-alterar-foto" class="btn-secundario campo-editavel<?= $modoEdicaoDeDados ? '' : ' escondido' ?>">Alterar Foto</button>
                            <?php if ($fotoUrl): ?>
                                <button type="button" id="botao-remover-foto" class="btn-secundario campo-editavel<?= $modoEdicaoDeDados ? '' : ' escondido' ?>">Remover Foto</button>
                            <?php endif; ?>
                            <span id="nome-arquivo-foto" class="campo-foto-nome-arquivo"></span>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="nome">Nome</label>
                        <input type="text" name="nome" id="nome" class="campo-editavel" value="<?= htmlspecialchars($_POST['nome'] ?? $usuarioLogado->nome, ENT_QUOTES, 'UTF-8') ?>" <?= $modoEdicaoDeDados ? '' : 'disabled' ?> required>
                    </div>

                    <div class="campo">
                        <label for="sobrenome">Sobrenome</label>
                        <input type="text" name="sobrenome" id="sobrenome" class="campo-editavel" value="<?= htmlspecialchars($_POST['sobrenome'] ?? $usuarioLogado->sobrenome, ENT_QUOTES, 'UTF-8') ?>" <?= $modoEdicaoDeDados ? '' : 'disabled' ?> required>
                    </div>

                    <div class="campo">
                        <label for="email">E-mail</label>
                        <input type="email" name="email" id="email" class="campo-editavel" value="<?= htmlspecialchars($_POST['email'] ?? $usuarioLogado->email, ENT_QUOTES, 'UTF-8') ?>" <?= $modoEdicaoDeDados ? '' : 'disabled' ?> required>
                    </div>

                    <div class="campo">
                        <label for="datanascimento">Data de Nascimento</label>
                        <input type="date" name="datanascimento" id="datanascimento" class="campo-editavel" value="<?= htmlspecialchars($dataNascimentoParaExibir, ENT_QUOTES, 'UTF-8') ?>" <?= $modoEdicaoDeDados ? '' : 'disabled' ?> required>
                    </div>

                    <button type="button" id="botao-editar-dados" class="btn-secundario<?= $modoEdicaoDeDados ? ' escondido' : '' ?>">Editar Dados</button>
                    <button type="submit" id="botao-salvar-dados" class="<?= $modoEdicaoDeDados ? '' : 'escondido' ?>">Salvar Alterações</button>
                </form>

                <form class="event-form-card" method="POST">
                    <h3>Trocar Senha</h3>
                    <input type="hidden" name="formulario" value="senha">
                    <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                    <p class="evento-mensagem<?= $erroSenha ? ' erro' : ($sucessoSenha ? ' sucesso' : '') ?>"><?= htmlspecialchars($erroSenha ?? ($sucessoSenha ? 'Senha atualizada!' : 'Opcional — deixe em branco pra manter a senha atual.'), ENT_QUOTES, 'UTF-8') ?></p>

                    <div class="campo">
                        <label for="senha_atual">Senha atual</label>
                        <input type="password" name="senha_atual" id="senha_atual" autocomplete="current-password">
                    </div>

                    <div class="campo">
                        <label for="senha_nova">Nova senha</label>
                        <input type="password" name="senha_nova" id="senha_nova" autocomplete="new-password">
                    </div>

                    <div class="campo">
                        <label for="senha_confirmar">Confirmar nova senha</label>
                        <input type="password" name="senha_confirmar" id="senha_confirmar" autocomplete="new-password">
                    </div>

                    <button type="submit">Salvar Alterações</button>
                </form>
            </div>
        </main>
    </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/notificacoes.js?v=<?= filemtime(__DIR__.'/public/assets/js/notificacoes.js') ?>"></script>
    <script src="public/assets/js/busca-menu.js?v=<?= filemtime(__DIR__.'/public/assets/js/busca-menu.js') ?>"></script>
    <script src="public/assets/js/perfil.js?v=<?= filemtime(__DIR__.'/public/assets/js/perfil.js') ?>"></script>
</body>
</html>
