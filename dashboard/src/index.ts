/**
 * Dashboard (dashboard/index.php): o botão de barras (☰, ao lado de
 * "Painel RB") abre um menu dropdown com as visões disponíveis do
 * painel:
 *   - "logistico-financeiro" (padrão): os 3 cards de Compras/Balanço
 *     de Produtos/Valor Total de Vendas;
 *   - "extrato-vendas": as 5 vendas mais recentes + o gráfico
 *     (doughnut) de vendas por mês do ano atual.
 *
 * Cada opção do menu tem um data-visao; o conteúdo correspondente é
 * qualquer elemento com a classe .visao-conteudo e o mesmo
 * data-visao — outras visões no futuro só precisam seguir esse mesmo
 * padrão, sem mexer na função de seleção.
 *
 * O gráfico só é montado (Chart.js, já carregado por
 * public/assets/js/chart.js antes deste script) na primeira vez que
 * "extrato-vendas" é selecionado — um canvas dentro de um container
 * escondido (display:none) tem largura/altura 0 no momento da
 * criação, então montar de cara (com a visão padrão sendo a outra)
 * resultaria num gráfico quebrado; nas vezes seguintes só chama
 * resize() pra ele se ajustar ao espaço (que só existe de verdade
 * depois de tirar o "escondido").
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/index.js.
 */
declare const Chart: any;

(function () {

let graficoDeVendas: any = null;

document.addEventListener('DOMContentLoaded', function () {
    ligarSeletorDeVisao();
});

function ligarSeletorDeVisao(): void {
    const botao = document.getElementById('botao-selecionar-visao');
    const menu = document.getElementById('menu-visoes');

    if (!botao || !menu) {
        return;
    }

    botao.addEventListener('click', function (evento) {
        evento.stopPropagation();
        const vaiAbrir = menu.classList.contains('escondido');
        menu.classList.toggle('escondido', !vaiAbrir);
        botao.setAttribute('aria-expanded', String(vaiAbrir));
    });

    document.addEventListener('click', function (evento) {
        if (!menu.classList.contains('escondido') && !menu.contains(evento.target as Node)) {
            menu.classList.add('escondido');
            botao.setAttribute('aria-expanded', 'false');
        }
    });

    menu.querySelectorAll<HTMLButtonElement>('.menu-visoes-opcao').forEach(function (opcao) {
        opcao.addEventListener('click', function () {
            selecionarVisao(opcao, menu);
            menu.classList.add('escondido');
            botao.setAttribute('aria-expanded', 'false');
        });
    });
}

function selecionarVisao(opcaoEscolhida: HTMLButtonElement, menu: HTMLElement): void {
    const visao = opcaoEscolhida.dataset.visao;

    menu.querySelectorAll('.menu-visoes-opcao').forEach(function (opcao) {
        opcao.classList.remove('ativo');
    });
    opcaoEscolhida.classList.add('ativo');

    document.querySelectorAll<HTMLElement>('.visao-conteudo').forEach(function (conteudo) {
        conteudo.classList.toggle('escondido', conteudo.dataset.visao !== visao);
    });

    if (visao === 'extrato-vendas') {
        garantirGraficoDeVendas();
    }
}

/** Cria o gráfico na primeira vez que a visão fica visível; nas próximas só reajusta o tamanho. */
function garantirGraficoDeVendas(): void {
    if (graficoDeVendas) {
        graficoDeVendas.resize();
        return;
    }

    const container = document.querySelector<HTMLElement>('.charts-products[data-visao="extrato-vendas"]');
    const canvas = document.getElementById('myChart');
    if (!container || !canvas) {
        return;
    }

    let vendasPorMes: number[];
    try {
        vendasPorMes = JSON.parse(container.dataset.vendasPorMes || '[]');
    } catch (erro) {
        vendasPorMes = [];
    }

    graficoDeVendas = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            datasets: [{
                label: 'Vendas por Mês',
                data: vendasPorMes,
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        },
    });
}

})();
