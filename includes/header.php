<!--
    Cabeçalho compartilhado: abre o <html>/<head>/<body> e o topo
    visual (logo + título). Incluído no início de cada página com
    `include 'includes/header.php';`; a página é responsável por
    fechar </body></html> via includes/footer.php.
-->
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bona+Nova+SC:ital,wght@0,400;0,700;1,400&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <!-- ?v=filemtime evita cache do navegador: muda sozinho quando o CSS é editado -->
    <link rel="stylesheet" href="stilo1.css?v=<?= filemtime(__DIR__ . '/../stilo1.css') ?>">
    <title>Royal Brasilian Extensions</title>
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/Royal_Brazilian_Extensions_logo_transparente.png" alt="Royal Brazilian Extensions">
        </div>
        <h1>Royal Brasilian Extensions</h1>
    </header>
