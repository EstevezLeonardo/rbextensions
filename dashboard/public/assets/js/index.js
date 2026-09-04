"use strict";
(function () {
    let graficoDeVendas = null;
    document.addEventListener('DOMContentLoaded', function () {
        ligarSeletorDeVisao();
    });
    function ligarSeletorDeVisao() {
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
            if (!menu.classList.contains('escondido') && !menu.contains(evento.target)) {
                menu.classList.add('escondido');
                botao.setAttribute('aria-expanded', 'false');
            }
        });
        menu.querySelectorAll('.menu-visoes-opcao').forEach(function (opcao) {
            opcao.addEventListener('click', function () {
                selecionarVisao(opcao, menu);
                menu.classList.add('escondido');
                botao.setAttribute('aria-expanded', 'false');
            });
        });
    }
    function selecionarVisao(opcaoEscolhida, menu) {
        const visao = opcaoEscolhida.dataset.visao;
        menu.querySelectorAll('.menu-visoes-opcao').forEach(function (opcao) {
            opcao.classList.remove('ativo');
        });
        opcaoEscolhida.classList.add('ativo');
        document.querySelectorAll('.visao-conteudo').forEach(function (conteudo) {
            conteudo.classList.toggle('escondido', conteudo.dataset.visao !== visao);
        });
        if (visao === 'extrato-vendas') {
            garantirGraficoDeVendas();
        }
    }
    /** Cria o gráfico na primeira vez que a visão fica visível; nas próximas só reajusta o tamanho. */
    function garantirGraficoDeVendas() {
        if (graficoDeVendas) {
            graficoDeVendas.resize();
            return;
        }
        const container = document.querySelector('.charts-products[data-visao="extrato-vendas"]');
        const canvas = document.getElementById('myChart');
        if (!container || !canvas) {
            return;
        }
        let vendasPorMes;
        try {
            vendasPorMes = JSON.parse(container.dataset.vendasPorMes || '[]');
        }
        catch (erro) {
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
