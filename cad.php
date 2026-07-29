<?php
$nome = '';
$sobrenome = '';
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $sobrenome = isset($_POST['sobrenome']) ? trim($_POST['sobrenome']) : '';
    $submitted = true;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <link rel="stylesheet" href="stilo1.css">
</head>
<body>
<header>
    <h1>Cadastro de Usuário</h1>
</header>
<section>
    <?php if ($submitted): ?>
        <div class="resultado">
            <p>Nome: <?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Sobrenome: <?php echo htmlspecialchars($sobrenome, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php endif; ?>

    <form action="cad.php" method="post">
        <label for="nome">Nome:</label>
        <input type="text" name="nome" id="idnome" value="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>">

        <label for="sobrenome">Sobrenome:</label>
        <input type="text" name="sobrenome" id="idsobrenome" value="<?php echo htmlspecialchars($sobrenome, ENT_QUOTES, 'UTF-8'); ?>">

        <input type="submit" value="Enviar">
    </form>
</section>
</body>
</html>
