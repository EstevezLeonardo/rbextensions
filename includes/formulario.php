<main>
    <section>
        <a href="index.php">
            <button class="btn btn-success">Voltar</button>
        </a>
    </section>
    <h2 class="mt-3">Cadastrar o Usuário</h2>

<form method="POST"> 
    <div class="form-group">
        <label for="nome">Nome:</label>
        <input type="text" class="form-control"  name="nome" required>
    </div>
    <div class="form-group">
        <label for="sobrenome">Sobrenome:</label>
        <input type="text" class="form-control"  name="sobrenome" required>
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" class="form-control"  name="email" required>
    </div>
    <div class="form-group">
        <label for="datanascimento">Data de Nascimento:</label>
        <input type="date" class="form-control"  name="datanascimento" required>
    </div>
    <div class="form-group">
         <button type="submit" class="btn btn-success">Enviar</button>
    </div>
    <div>
        <div class="form-check form-check-inline">
    </div>

</main>