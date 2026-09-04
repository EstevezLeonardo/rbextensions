/**
 * Caixa "Pesquisar opções" do cabeçalho (nav .form-group) — incluído em
 * toda página que usa o cabeçalho padrão (dashboard/ e usuarios/).
 * Busca global (dashboard/busca-global.php): produtos, clientes e
 * vendas que batem com o termo digitado, numa lista suspensa logo
 * abaixo da caixa. Só dispara a partir de 2 caracteres, com um
 * pequeno atraso (debounce) pra não buscar a cada letra.
 *
 * Os links de cada resultado usam caminho absoluto (/rbextensions/...)
 * porque este script roda tanto em páginas de dashboard/ quanto de
 * usuarios/ (profundidades diferentes) — mesmo padrão já usado em
 * dashboard/src/notificacoes.ts e em App\Session\Login pros redirects
 * de login/logout:
 *   - produto  → dashboard/controle-produtos.php?busca=<código>
 *     (essa página já lê ?busca= e roda a busca sozinha ao carregar)
 *   - cliente  → usuarios/listar.php?busca=<nome>
 *     (idem — usuarios/listar.php já lê ?busca= no PHP)
 *   - venda    → dashboard/vendas.php?venda_id=<id>
 *     (mesmo link do botão "Venda" de dashboard/index.php — abre já
 *     filtrado no extrato daquela venda específica)
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/busca-menu.js.
 */
(function () {

interface ResultadoProduto {
    id: number | string;
    codigo: string;
    nome: string;
}

interface ResultadoCliente {
    id: number | string;
    nome: string;
    sobrenome: string;
}

interface ResultadoVenda {
    id: number | string;
    cliente: string;
    valorTotal: number;
}

/** Formato da resposta de dashboard/busca-global.php. */
interface RespostaBuscaGlobal {
    produtos: ResultadoProduto[];
    clientes: ResultadoCliente[];
    vendas: ResultadoVenda[];
}

/** Um item já convertido em texto + link, pronto pra desenhar (produto/cliente/venda viram isso). */
interface ItemDeResultado {
    texto: string;
    href: string;
}

document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector<HTMLElement>('nav .form-group .rows');
    const input = document.querySelector<HTMLInputElement>('nav .form-group input[name="search"]');
    const form = document.querySelector<HTMLFormElement>('nav .form-group');

    if (!container || !input || !form) {
        return;
    }

    const resultadosEl = document.createElement('div');
    resultadosEl.className = 'busca-resultados escondido';
    container.appendChild(resultadosEl);

    let timeoutId: number | undefined;

    // Enter não deve recarregar a página (não existe uma "página de resultados" — só o dropdown)
    form.addEventListener('submit', function (evento) {
        evento.preventDefault();
    });

    input.addEventListener('input', function () {
        window.clearTimeout(timeoutId);
        const termo = input.value.trim();

        if (termo.length < 2) {
            resultadosEl.classList.add('escondido');
            return;
        }

        timeoutId = window.setTimeout(function () {
            buscarGlobal(termo, resultadosEl);
        }, 300);
    });

    // volta a mostrar o último resultado se a pessoa clicar de novo no campo sem apagar o texto
    input.addEventListener('focus', function () {
        if (resultadosEl.childElementCount > 0 && input.value.trim().length >= 2) {
            resultadosEl.classList.remove('escondido');
        }
    });

    document.addEventListener('click', function (evento) {
        if (!container.contains(evento.target as Node)) {
            resultadosEl.classList.add('escondido');
        }
    });
});

function buscarGlobal(termo: string, resultadosEl: HTMLElement): void {
    fetch('/rbextensions/dashboard/busca-global.php?q=' + encodeURIComponent(termo))
        .then(function (resposta) {
            return resposta.json() as Promise<RespostaBuscaGlobal>;
        })
        .then(function (dados) {
            desenharResultados(dados, resultadosEl);
        })
        .catch(function () {
            resultadosEl.classList.add('escondido');
        });
}

function desenharResultados(dados: RespostaBuscaGlobal, resultadosEl: HTMLElement): void {
    resultadosEl.innerHTML = '';

    const total = dados.produtos.length + dados.clientes.length + dados.vendas.length;

    if (total === 0) {
        const vazio = document.createElement('p');
        vazio.className = 'busca-resultados-vazio';
        vazio.textContent = 'Nenhum resultado encontrado.';
        resultadosEl.appendChild(vazio);
        resultadosEl.classList.remove('escondido');
        return;
    }

    if (dados.produtos.length > 0) {
        adicionarGrupoDeResultados(resultadosEl, 'Produtos', dados.produtos.map(function (produto): ItemDeResultado {
            return {
                texto: produto.codigo + ' — ' + produto.nome,
                href: '/rbextensions/dashboard/controle-produtos.php?busca=' + encodeURIComponent(produto.codigo),
            };
        }));
    }

    if (dados.clientes.length > 0) {
        adicionarGrupoDeResultados(resultadosEl, 'Clientes', dados.clientes.map(function (cliente): ItemDeResultado {
            return {
                texto: cliente.nome + ' ' + cliente.sobrenome,
                href: '/rbextensions/usuarios/listar.php?busca=' + encodeURIComponent(cliente.nome),
            };
        }));
    }

    if (dados.vendas.length > 0) {
        adicionarGrupoDeResultados(resultadosEl, 'Vendas', dados.vendas.map(function (venda): ItemDeResultado {
            const valor = venda.valorTotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            return {
                texto: 'Venda #' + venda.id + ' — ' + venda.cliente + ' (' + valor + ')',
                href: '/rbextensions/dashboard/vendas.php?venda_id=' + venda.id,
            };
        }));
    }

    resultadosEl.classList.remove('escondido');
}

function adicionarGrupoDeResultados(resultadosEl: HTMLElement, titulo: string, itens: ItemDeResultado[]): void {
    const tituloEl = document.createElement('p');
    tituloEl.className = 'busca-resultados-grupo-titulo';
    tituloEl.textContent = titulo;
    resultadosEl.appendChild(tituloEl);

    itens.forEach(function (item) {
        const link = document.createElement('a');
        link.href = item.href;
        link.textContent = item.texto;
        resultadosEl.appendChild(link);
    });
}

})();
