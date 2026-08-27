<?php

/**
 * A página inicial de verdade mora em usuarios/index.php — este
 * arquivo só existe pra quem acessar a raiz do site (/rbextensions/)
 * direto não cair numa listagem de pastas ou página em branco.
 */

header('Location: usuarios/index.php');
exit;
