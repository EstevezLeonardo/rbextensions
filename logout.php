<?php

/**
 * Encerra a sessão do usuário (Login::logout()) e volta para a tela
 * de login.
 */

require 'vendor/autoload.php';

use App\Session\Login;

Login::logout();

header('Location: login.php');
exit;
