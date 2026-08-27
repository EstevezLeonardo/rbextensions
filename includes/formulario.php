<!--
    Formulário de cadastro/edição de usuário, reaproveitado tanto por
    cadas.php quanto por editar.php. Espera as constantes/variáveis
    TITLE (título da página) e $obVaga (dados a pré-preencher; vazio
    no cadastro, preenchido na edição).
-->
<main>

    <h2><?= TITLE?></h2>

    <form method="POST">
        <!-- token CSRF: confirmado em cadas.php/editar.php antes de processar o POST -->
        <input type="hidden" name="csrf_token" value="<?= \App\Session\Csrf::token() ?>">

        <label for="nome">Nome:</label>
        <input type="text" name="nome" id="nome" value="<?= $obVaga->nome ?? '' ?>" required>

        <label for="sobrenome">Sobrenome:</label>
        <input type="text" name="sobrenome" id="sobrenome" value="<?= $obVaga->sobrenome ?? '' ?>" required>

        <fieldset>
            <legend>Status:</legend>

            <input type="radio" name="ativo" id="ativo_s" value="s" <?= (isset($obVaga->ativo) && $obVaga->ativo === 's') ? 'checked' : '' ?> required>
            <label for="ativo_s">Ativo</label>

            <input type="radio" name="ativo" id="ativo_n" value="n" <?= (isset($obVaga->ativo) && $obVaga->ativo === 'n') ? 'checked' : '' ?> required>
            <label for="ativo_n">Inativo</label>
        </fieldset>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?= $obVaga->email ?? '' ?>" required>

        <label for="datanascimento">Data de Nascimento:</label>
        <input type="date" name="datanascimento" id="datanascimento" value="<?= $obVaga->datanascimento ?? '' ?>" required>

        <label for="senha">Senha:</label>
        <input type="password" name="senha" id="senha" required>

        <input type="submit" value="Enviar">
    </form>

    <section>
        <a href="index.php">
            <button>Voltar</button>
        </a>
    </section>
</main>
