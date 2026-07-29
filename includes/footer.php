<header>
    <h1>Cadastro de Usuário</h1>
</header>
<section>
    <?php if (!empty($submitted)): ?>
        <div class="resultado">
            <p>Nome: <?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Sobrenome: <?php echo htmlspecialchars($sobrenome, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php endif; ?>

    <form action="" method="post">
        <label for="nome">Nome:</label>
        <input type="text" name="nome" id="idnome" value="<?php echo isset($nome) ? htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') : ''; ?>">

        <label for="sobrenome">Sobrenome:</label>
        <input type="text" name="sobrenome" id="idsobrenome" value="<?php echo isset($sobrenome) ? htmlspecialchars($sobrenome, ENT_QUOTES, 'UTF-8') : ''; ?>">

        <input type="submit" value="Enviar">
    </form>
</section>
</body>
</html>