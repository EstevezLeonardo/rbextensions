<!--
    Formulário de login, incluído por login.php. Espera $mensagem
    (erro/aviso, opcional) e $obVaga (para reaproveitar o email
    digitado caso o login falhe).
-->
<main>
    <?php if (!empty($mensagem)) : ?>
        <p><?= $mensagem ?></p>
    <?php endif; ?>
    <h2>Login</h2>

    <form method="POST">
        <!-- token CSRF: confirmado em login.php antes de processar o POST -->
        <input type="hidden" name="csrf_token" value="<?= \App\Session\Csrf::token() ?>">

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?= $obVaga->email ?? '' ?>" required>
        
        <label for="senha">Senha:</label>
        <input type="password" name="senha" id="senha" required>

        <input type="submit" name="acao" value="Fazer Login">

        <p>Não possui uma conta? <a href="cadas.php">Cadastre-se</a></p>
    </form>
    
    <section>
        <a href="index.php">
            <button>Voltar</button>
        </a>
    </section>

</main>