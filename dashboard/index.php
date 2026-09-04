<?php

/**
 * Dashboard (painel principal pós-login). Exige login, verificado
 * via a mesma camada de sessão do restante do projeto (require
 * relativo ao vendor/autoload.php do próprio rbextensions).
 *
 * Os números dos cards e a linha da tabela abaixo ainda são
 * mockados/estáticos (não vêm do banco) — layout inicial do painel,
 * a ser ligado a dados reais depois.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard RB</title>
    <link rel="stylesheet" href="public/assets/css/all.css">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css?v=<?= filemtime(__DIR__.'/public/assets/css/style.css') ?>">
</head>
<body>
            
    <div class="container-fluid">
        <header class="head">
            
            <nav>
                <div class="logo">
                    <a href="#">
                        <img src="public/assets/images/Royal_Brazilian_Extensions_logo_transparente.png" alt="Logo">
                    </a> 

                </div>
                <form action="" class="form-group">
                    <div class="rows">
                        <input type="text" name="search" class="form-control rounded-0" placeholder="Pesquisar opções">
                        <i class="fa-solid fa-search"></i>
                    </div>
                </form>
                
                    <ul>
                        <li><a href="index.php" class="actives"><span><i class="fa-solid fa-home"></i></span>Home</a></li>
                        <li><a href="agenda.php"><span><i class="fa-solid fa-calendar-alt"></i></span>Agenda</a></li>
                        <li><a href=""><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
                        <li><a href="controle-produtos.php"><span><i class="fa-solid fa-box"></i></span>Produtos</a></li>
                        <li><a href="servicos.php"><span><i class="fa-solid fa-concierge-bell"></i></span>Serviços</a></li>
                        <li><a href="../usuarios/listar.php"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
                        <li><a href="vendas.php"><span><i class="fa-solid fa-shopping-cart"></i></span>Vendas</a></li>     
                        <li><a href="estoque.php"><span><i class="fa-solid fa-warehouse"></i></span>Estoque</a></li>
                        <li><a href="financeiro.php"><span><i class="fa-solid fa-dollar"></i></span>Financeiro</a></li>
                    </ul>

            </nav>

        </header>
        <main>
            <div class="nav-top">
                <div class="bars">
                    <button class="btns"><i class="fa-solid fa-bars"></i></button>
                    <p>Painel RB</p>
                </div>
                    <div class="user-notification">
                        <button 
                        class="users">
                            <p>Olá, <span><?= htmlspecialchars($usuarioLogado->nome, ENT_QUOTES, 'UTF-8') ?></span></p>
                            <i class="fa-solid fa-user"></i>
                        </button>
                        <button class="notification">
                            <i class="fa-solid fa-bell"></i>
                            <span>1</span>
                        </button>
                        <a href="../usuarios/logout.php" title="Sair">
                            <button class="notification logout">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </a>
                    </div>
                
            </div> 
            <div class="container-fluids">
                <div class="cards">
                    <div class="cards-header">
                        <div class="cards-top">
                                <div class="card-number">
                                    <p class="value-total">0</p>
                                    <p class="days">Vendas hoje</p>
                                </div>
                               
                                <div class="cards-icons">
                                    <i class="fa-solid fa-shopping-cart"></i>
                                </div>
                            </div>
                        
                        <div class="cards-type">
                            <p class="ticket">
                                Compras Finalizadas
                            </p>
                        </div>
                    </div>
                    
                    <div class="cards-header">
                        <div class="cards-top">
                                <div class="card-number">
                                    <p class="value-total">0</p>
                                    <p class="days">Entradas/Saídas hoje</p>
                                </div>
                               
                                <div class="cards-icons">
                                    <i class="fa-solid fa-warehouse"></i>
                                </div>
                            </div>
                        
                        <div class="cards-type">
                            <p class="ticket">
                                Balanço de Produtos
                            </p>
                        </div>
                    </div>

                    <div class="cards-header">
                        <div class="cards-top">
                                <div class="card-number">
                                    <p class="value-total">0</p>
                                    <p class="days">Valor</p>
                                </div>
                               
                                <div class="cards-icons">
                                    <i class="fa-solid fa-dollar"></i>
                                </div>
                            </div>
                        
                        <div class="cards-type">
                            <p class="ticket">
                                Valor Total das Vendas
                            </p>
                        </div>
                    </div>
                </div> 
                <div class="containers-fluid">
                    <div class="rows-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Cliente 1</td>
                                    <td>000,00</td>
                                    <td><button class="btn btn-sm">Venda</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="charts-products">
                        <canvas id="myChart"></canvas>
                    </div>

                </div>       
            </div>
        </main>
        </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/chart.js"></script>

    <script>
  const ctx = document.getElementById('myChart');

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
      datasets: [{
        label: 'Vendas por Mês',
        data: [10, 20, 30, 40, 50, 60,],
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
    </script>
</body>
</html>