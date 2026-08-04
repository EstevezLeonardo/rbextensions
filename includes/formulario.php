<main>
        <section>
            <a href="index.php">
                <button class="btn btn-success">Voltar</button>
            </a>
        </section>
        <h2 class="mt-3"><?= TITLE?></h2>

    <form method="POST"> 
        <div class="form-group">
            <label for="nome">Nome:</label>
            <input type="text" class="form-control"  name="nome" value="<?= $obVaga->nome ?? '' ?>" required>
        </div>
        <div class="form-group">
            <label for="sobrenome">Sobrenome:</label>
            <input type="text" class="form-control"  name="sobrenome" value="<?= $obVaga->sobrenome ?? '' ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control"  name="email" value="<?= $obVaga->email ?? '' ?>" required>
        </div>
        <div class="form-group">
            <label for="datanascimento">Data de Nascimento:</label>
            <input type="datetime-local" class="form-control" name="datanascimento" value="<?= $obVaga->datanascimento ?? '' ?>" required>
        </div>
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" class="form-control"  name="senha" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-success">Enviar</button>
        </div>
        <div>
            <div class="form-check form-check-inline">
        </div>
    </form>
</main>