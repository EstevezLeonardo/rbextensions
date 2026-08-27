<?php

require 'vendor/autoload.php';

use App\Session\Login;

Login::logout();

header('Location: login.php');
exit;
