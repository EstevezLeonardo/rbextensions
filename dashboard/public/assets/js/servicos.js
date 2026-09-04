"use strict";
/**
 * Serviços (dashboard/servicos.php): envio de e-mail e caixa de
 * entrada da conta logada, via API do Gmail (OAuth2 — "Conectar
 * E-mail" é um link normal que navega pro Google e volta, não tem
 * nada pra este arquivo ligar nele).
 *
 * Liga:
 *   - o botão "Sair do E-mail" (revoga e apaga o token salvo,
 *     dashboard/servicos-desconectar-email.php);
 *   - o formulário "Enviar E-mail" (dashboard/servicos-enviar.php);
 *   - o menu de pastas (Caixa de Entrada/Itens Enviados/Rascunhos/
 *     Lixeira) e o filtro por data/período, que juntos decidem o que
 *     a lista "Caixa de Entrada" (#lista-inbox) busca em
 *     dashboard/servicos-inbox-listar.php — a API do Gmail só pagina
 *     "pra frente" (token opaco), por isso a paginação aqui é
 *     Anterior/Próxima, guardando os tokens já vistos;
 *   - o botão "Ler" de cada item, que abre o corpo completo logo
 *     abaixo (dashboard/servicos-inbox-ler.php).
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/servicos.js.
 *
 * Tudo dentro de uma IIFE: como não usamos módulos (sem bundler), os
 * .ts compilados viram scripts globais — sem isso, nomes de função
 * repetidos entre este arquivo e outras páginas (ex: controle-produtos.ts)
 * colidiriam no escopo global e o `tsc` recusaria compilar.
 */
(function () {
    /**
     * Estado da Caixa de Entrada: pasta atual e a "pilha" de page tokens
     * já vistos (índice 0 = primeira página, sempre null). Trocar de
     * pasta ou de filtro de data zera a pilha, porque os tokens só valem
     * pra sequência de busca que os gerou.
     */
    const estadoInbox = {
        pasta: 'caixa',
        tokens: [null],
        indice: 0,
    };
    document.addEventListener('DOMContentLoaded', function () {
        ligarBotaoDeSair();
        ligarAnexosDoEnvio();
        ligarFormularioDeEnvio();
        ligarMenuDePastas();
        ligarFiltroDeData();
        ligarPaginacao();
        const botaoAtualizar = document.getElementById('botao-inbox-atualizar');
        if (botaoAtualizar) {
            botaoAtualizar.addEventListener('click', function () {
                carregarCaixaDeEntrada();
            });
        }
        // só tenta carregar se já tem conta conectada (botão "Sair" visível == conectado)
        const botaoSair = document.getElementById('botao-email-sair');
        if (botaoSair && !botaoSair.classList.contains('escondido')) {
            carregarCaixaDeEntrada();
        }
    });
    /** Reinicia a pilha de páginas (usado ao trocar pasta/filtro) e recarrega a partir da primeira página. */
    function reiniciarEPaginar() {
        estadoInbox.tokens = [null];
        estadoInbox.indice = 0;
        carregarCaixaDeEntrada();
    }
    /** Liga "Sair do E-mail" a dashboard/servicos-desconectar-email.php. */
    function ligarBotaoDeSair() {
        const botaoSair = document.getElementById('botao-email-sair');
        const botaoConectar = document.getElementById('botao-email-conectar');
        const inboxMensagemEl = document.getElementById('inbox-mensagem');
        if (!botaoSair) {
            return;
        }
        botaoSair.addEventListener('click', function () {
            const csrfToken = document.getElementById('email-acoes-csrf-token').value;
            fetch('servicos-desconectar-email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: csrfToken }),
            })
                .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível sair do e-mail.');
                    }
                    return dados;
                });
            })
                .then(function () {
                botaoSair.classList.add('escondido');
                if (botaoConectar) {
                    botaoConectar.classList.remove('escondido');
                }
                const listaEl = document.getElementById('lista-inbox');
                const paginacaoEl = document.getElementById('paginacao-inbox');
                if (listaEl)
                    listaEl.innerHTML = '';
                if (paginacaoEl)
                    paginacaoEl.innerHTML = '';
                if (inboxMensagemEl) {
                    inboxMensagemEl.textContent = 'Você saiu do e-mail. Conecte de novo pra continuar usando.';
                    inboxMensagemEl.className = 'evento-mensagem';
                }
            })
                .catch(function (erro) {
                if (inboxMensagemEl) {
                    inboxMensagemEl.textContent = erro.message;
                    inboxMensagemEl.className = 'evento-mensagem erro';
                }
            });
        });
    }
    /** Liga "Anexar" ao input de arquivo escondido, e desenha a lista do que foi escolhido (com um "✕" pra tirar antes de enviar). */
    function ligarAnexosDoEnvio() {
        const botaoAnexar = document.getElementById('botao-email-anexar');
        const inputAnexos = document.getElementById('email-anexos-input');
        const listaAnexos = document.getElementById('email-anexos-lista');
        if (!botaoAnexar || !inputAnexos || !listaAnexos) {
            return;
        }
        botaoAnexar.addEventListener('click', function () {
            inputAnexos.click();
        });
        inputAnexos.addEventListener('change', function () {
            desenharAnexosSelecionados(inputAnexos, listaAnexos);
        });
    }
    /** Redesenha a lista de arquivos escolhidos em #email-anexos-input a partir do FileList atual. */
    function desenharAnexosSelecionados(inputAnexos, listaAnexos) {
        listaAnexos.innerHTML = '';
        Array.from(inputAnexos.files || []).forEach(function (arquivo, indice) {
            const item = document.createElement('li');
            const nome = document.createElement('span');
            nome.textContent = arquivo.name + ' (' + formatarTamanhoDeArquivo(arquivo.size) + ')';
            const remover = document.createElement('button');
            remover.type = 'button';
            remover.textContent = '✕';
            remover.addEventListener('click', function () {
                removerAnexoSelecionado(inputAnexos, listaAnexos, indice);
            });
            item.appendChild(nome);
            item.appendChild(remover);
            listaAnexos.appendChild(item);
        });
    }
    /** Tira um arquivo da seleção de #email-anexos-input (o FileList é somente-leitura, por isso o DataTransfer). */
    function removerAnexoSelecionado(inputAnexos, listaAnexos, indiceRemovido) {
        const transferencia = new DataTransfer();
        Array.from(inputAnexos.files || []).forEach(function (arquivo, indice) {
            if (indice !== indiceRemovido) {
                transferencia.items.add(arquivo);
            }
        });
        inputAnexos.files = transferencia.files;
        desenharAnexosSelecionados(inputAnexos, listaAnexos);
    }
    /** Liga o formulário "Enviar E-mail" a dashboard/servicos-enviar.php. */
    function ligarFormularioDeEnvio() {
        const form = document.getElementById('form-email-envio');
        const mensagemEl = document.getElementById('email-envio-mensagem');
        const botaoEnviarEl = document.getElementById('botao-email-enviar');
        const inputAnexos = document.getElementById('email-anexos-input');
        const listaAnexos = document.getElementById('email-anexos-lista');
        if (!form || !mensagemEl || !botaoEnviarEl) {
            return;
        }
        form.addEventListener('submit', function (evento) {
            evento.preventDefault();
            const csrfToken = document.getElementById('email-envio-csrf-token').value;
            const dadosDoFormulario = new FormData();
            dadosDoFormulario.append('destinatario', document.getElementById('email-destinatario').value);
            dadosDoFormulario.append('assunto', document.getElementById('email-assunto').value);
            dadosDoFormulario.append('mensagem', document.getElementById('email-mensagem').value);
            dadosDoFormulario.append('csrf_token', csrfToken);
            Array.from((inputAnexos === null || inputAnexos === void 0 ? void 0 : inputAnexos.files) || []).forEach(function (arquivo) {
                dadosDoFormulario.append('anexos[]', arquivo, arquivo.name);
            });
            botaoEnviarEl.disabled = true;
            mensagemEl.textContent = '';
            mensagemEl.className = 'evento-mensagem';
            fetch('servicos-enviar.php', {
                method: 'POST',
                body: dadosDoFormulario,
            })
                .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível enviar o e-mail.');
                    }
                    return dados;
                });
            })
                .then(function () {
                form.reset();
                if (inputAnexos)
                    inputAnexos.value = '';
                if (listaAnexos)
                    listaAnexos.innerHTML = '';
                mensagemEl.textContent = 'E-mail enviado!';
                mensagemEl.className = 'evento-mensagem sucesso';
                botaoEnviarEl.disabled = false;
            })
                .catch(function (erro) {
                mensagemEl.textContent = erro.message;
                mensagemEl.className = 'evento-mensagem erro';
                botaoEnviarEl.disabled = false;
            });
        });
    }
    /** Liga os botões do menu de pastas (Caixa de Entrada/Enviados/Rascunhos/Lixeira). */
    function ligarMenuDePastas() {
        const botoes = document.querySelectorAll('.inbox-pasta-botao');
        botoes.forEach(function (botao) {
            botao.addEventListener('click', function () {
                if (botao.classList.contains('ativo')) {
                    return;
                }
                botoes.forEach(function (outro) {
                    outro.classList.remove('ativo');
                });
                botao.classList.add('ativo');
                estadoInbox.pasta = botao.dataset.pasta || 'caixa';
                reiniciarEPaginar();
            });
        });
    }
    /**
     * Liga "Filtrar" (aplica datas + busca por remetente/assunto) e
     * "Limpar" (remove os dois) do menu ao lado da lista. A busca também
     * aplica ao apertar Enter no campo, sem precisar clicar em "Filtrar".
     */
    function ligarFiltroDeData() {
        const botaoFiltrar = document.getElementById('botao-inbox-filtrar');
        const botaoLimpar = document.getElementById('botao-inbox-limpar-filtro');
        const dataInicioEl = document.getElementById('inbox-data-inicio');
        const dataFimEl = document.getElementById('inbox-data-fim');
        const buscaEl = document.getElementById('inbox-busca-texto');
        if (botaoFiltrar) {
            botaoFiltrar.addEventListener('click', function () {
                reiniciarEPaginar();
            });
        }
        if (botaoLimpar) {
            botaoLimpar.addEventListener('click', function () {
                if (dataInicioEl)
                    dataInicioEl.value = '';
                if (dataFimEl)
                    dataFimEl.value = '';
                if (buscaEl)
                    buscaEl.value = '';
                reiniciarEPaginar();
            });
        }
        if (buscaEl) {
            buscaEl.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    evento.preventDefault();
                    reiniciarEPaginar();
                }
            });
        }
    }
    /** Liga os botões "◀ Anterior" e "Próxima ▶" de #paginacao-inbox. */
    function ligarPaginacao() {
        const container = document.getElementById('paginacao-inbox');
        if (!container) {
            return;
        }
        const botaoAnterior = document.createElement('button');
        botaoAnterior.type = 'button';
        botaoAnterior.id = 'botao-inbox-anterior';
        botaoAnterior.textContent = '◀ Anterior';
        botaoAnterior.addEventListener('click', function () {
            if (estadoInbox.indice > 0) {
                estadoInbox.indice -= 1;
                carregarCaixaDeEntrada();
            }
        });
        const botaoProxima = document.createElement('button');
        botaoProxima.type = 'button';
        botaoProxima.id = 'botao-inbox-proxima';
        botaoProxima.textContent = 'Próxima ▶';
        botaoProxima.addEventListener('click', function () {
            if (estadoInbox.tokens[estadoInbox.indice + 1] !== undefined) {
                estadoInbox.indice += 1;
                carregarCaixaDeEntrada();
            }
        });
        container.appendChild(botaoAnterior);
        container.appendChild(botaoProxima);
    }
    /** Habilita/desabilita "Anterior"/"Próxima" conforme a posição atual e se há mais páginas. */
    function atualizarBotoesDePaginacao(temProximaPagina) {
        const botaoAnterior = document.getElementById('botao-inbox-anterior');
        const botaoProxima = document.getElementById('botao-inbox-proxima');
        if (botaoAnterior) {
            botaoAnterior.disabled = estadoInbox.indice === 0;
        }
        if (botaoProxima) {
            botaoProxima.disabled = !temProximaPagina;
        }
    }
    /**
     * Busca a pasta/página/filtro atuais em
     * dashboard/servicos-inbox-listar.php e desenha a lista numerada
     * #lista-inbox, guardando o token da próxima página quando houver.
     */
    function carregarCaixaDeEntrada() {
        const listaEl = document.getElementById('lista-inbox');
        const mensagemEl = document.getElementById('inbox-mensagem');
        const dataInicioEl = document.getElementById('inbox-data-inicio');
        const dataFimEl = document.getElementById('inbox-data-fim');
        const buscaEl = document.getElementById('inbox-busca-texto');
        if (!listaEl) {
            return;
        }
        if (mensagemEl) {
            mensagemEl.textContent = 'Carregando...';
            mensagemEl.className = 'evento-mensagem';
        }
        const parametros = new URLSearchParams({
            pasta: estadoInbox.pasta,
            page_token: estadoInbox.tokens[estadoInbox.indice] || '',
            data_inicio: dataInicioEl ? dataInicioEl.value : '',
            data_fim: dataFimEl ? dataFimEl.value : '',
            busca: buscaEl ? buscaEl.value.trim() : '',
        });
        fetch('servicos-inbox-listar.php?' + parametros.toString())
            .then(function (resposta) {
            return resposta.json().then(function (dados) {
                if (!resposta.ok) {
                    throw new Error(dados.erro || 'Não foi possível carregar essa pasta.');
                }
                return dados;
            });
        })
            .then(function (dados) {
            estadoInbox.tokens[estadoInbox.indice + 1] = dados.proximoPageToken;
            desenharListaDeInbox(dados.mensagens, listaEl, mensagemEl);
            atualizarBotoesDePaginacao(!!dados.proximoPageToken);
        })
            .catch(function (erro) {
            listaEl.innerHTML = '';
            atualizarBotoesDePaginacao(false);
            if (mensagemEl) {
                mensagemEl.textContent = erro.message;
                mensagemEl.className = 'evento-mensagem erro';
            }
        });
    }
    /**
     * Monta os <li> da lista a partir das mensagens recebidas. Usa
     * textContent (nunca innerHTML) pros textos da mensagem, pra um
     * assunto/remetente com caracteres de HTML não virar código na
     * página.
     */
    function desenharListaDeInbox(mensagens, listaEl, mensagemEl) {
        listaEl.innerHTML = '';
        if (mensagemEl) {
            mensagemEl.textContent = mensagens.length === 0 ? 'Nenhuma mensagem encontrada.' : '';
            mensagemEl.className = 'evento-mensagem';
        }
        mensagens.forEach(function (mensagem) {
            const item = document.createElement('li');
            item.className = 'evento-item';
            const info = document.createElement('div');
            info.className = 'evento-item-info';
            const titulo = document.createElement('strong');
            titulo.textContent = (mensagem.lida ? '' : '● ') + mensagem.assunto;
            const detalhes = document.createElement('span');
            detalhes.textContent = mensagem.de + ' · ' + mensagem.data;
            info.appendChild(titulo);
            info.appendChild(detalhes);
            const corpoEl = document.createElement('div');
            corpoEl.className = 'email-corpo escondido';
            const acoes = document.createElement('div');
            acoes.className = 'evento-item-acoes';
            const botaoLer = document.createElement('button');
            botaoLer.type = 'button';
            botaoLer.className = 'btn-editar';
            botaoLer.textContent = 'Ler';
            botaoLer.addEventListener('click', function () {
                alternarCorpoDaMensagem(mensagem.uid, corpoEl, botaoLer);
            });
            acoes.appendChild(botaoLer);
            item.appendChild(info);
            item.appendChild(acoes);
            item.appendChild(corpoEl);
            listaEl.appendChild(item);
        });
    }
    /**
     * Abre/fecha o corpo de uma mensagem. Na primeira vez que abre, busca
     * o conteúdo em dashboard/servicos-inbox-ler.php; nas próximas, só
     * mostra/esconde o que já foi carregado (controlado por
     * data-carregado, já que com HTML o corpo vira um <iframe> e
     * corpoEl.textContent fica vazio mesmo depois de carregado). Enquanto
     * uma mensagem está aberta, os outros itens da lista ficam escondidos
     * — só volta a mostrar todos quando ela é fechada.
     */
    function alternarCorpoDaMensagem(uid, corpoEl, botaoLer) {
        const item = corpoEl.closest('li');
        if (!corpoEl.classList.contains('escondido')) {
            corpoEl.classList.add('escondido');
            botaoLer.textContent = 'Ler';
            alternarOutrosItensDaLista(item, false);
            return;
        }
        alternarOutrosItensDaLista(item, true);
        if (corpoEl.dataset.carregado) {
            corpoEl.classList.remove('escondido');
            botaoLer.textContent = 'Fechar';
            return;
        }
        botaoLer.disabled = true;
        botaoLer.textContent = 'Carregando...';
        fetch('servicos-inbox-ler.php?uid=' + encodeURIComponent(uid))
            .then(function (resposta) {
            return resposta.json().then(function (dados) {
                if (!resposta.ok) {
                    throw new Error(dados.erro || 'Não foi possível abrir essa mensagem.');
                }
                return dados;
            });
        })
            .then(function (dados) {
            desenharCorpoDaMensagem(dados, corpoEl);
            corpoEl.dataset.carregado = '1';
            corpoEl.classList.remove('escondido');
            botaoLer.textContent = 'Fechar';
            botaoLer.disabled = false;
        })
            .catch(function (erro) {
            corpoEl.textContent = erro.message;
            corpoEl.classList.remove('escondido');
            botaoLer.textContent = 'Fechar';
            botaoLer.disabled = false;
        });
    }
    /** Esconde (ou volta a mostrar) todo item de #lista-inbox que não seja o aberto agora. */
    function alternarOutrosItensDaLista(itemAberto, esconderOutros) {
        if (!itemAberto || !itemAberto.parentElement) {
            return;
        }
        Array.from(itemAberto.parentElement.children).forEach(function (irmao) {
            if (irmao !== itemAberto) {
                irmao.classList.toggle('escondido', esconderOutros);
            }
        });
    }
    /**
     * Desenha o corpo já carregado: se veio HTML (corpoHtml), num <iframe
     * sandbox="allow-popups allow-same-origin"> — nunca allow-scripts, e o
     * HTML já chega sanitizado do backend (GmailApi::sanitizarHtml), mas
     * mesmo assim mantemos JS impossível de rodar aí dentro; isolado do
     * DOM da página, senão um <style> do próprio e-mail vazaria e
     * quebraria o resto do dashboard. Senão (mensagem só texto), como
     * antes, em texto puro via textContent. Anexos de arquivo (não as
     * imagens inline do template) viram links de download logo abaixo.
     */
    function desenharCorpoDaMensagem(dados, corpoEl) {
        corpoEl.innerHTML = '';
        if (dados.corpoHtml !== null) {
            const iframe = document.createElement('iframe');
            iframe.sandbox.add('allow-popups', 'allow-same-origin');
            iframe.srcdoc =
                '<!doctype html><html><head><meta charset="utf-8">' +
                    '<base target="_blank">' +
                    '<style>body{margin:8px;font-family:sans-serif;word-wrap:break-word;}img{max-width:100%;}</style>' +
                    '</head><body>' + dados.corpoHtml + '</body></html>';
            iframe.addEventListener('load', function () {
                const documentoInterno = iframe.contentDocument;
                if (documentoInterno) {
                    iframe.style.height = documentoInterno.documentElement.scrollHeight + 'px';
                }
            });
            corpoEl.appendChild(iframe);
        }
        else {
            const textoEl = document.createElement('div');
            textoEl.textContent = dados.corpo || '';
            corpoEl.appendChild(textoEl);
        }
        if (dados.anexos.length > 0) {
            const lista = document.createElement('ul');
            lista.className = 'email-anexos';
            dados.anexos.forEach(function (anexo) {
                const item = document.createElement('li');
                const link = document.createElement('a');
                const parametros = new URLSearchParams({
                    uid: dados.uid,
                    attachmentId: anexo.attachmentId,
                    nome: anexo.nome,
                    tipo: anexo.tipo,
                });
                link.href = 'servicos-inbox-anexo.php?' + parametros.toString();
                link.textContent = '📎 ' + anexo.nome + ' (' + formatarTamanhoDeArquivo(anexo.tamanho) + ')';
                item.appendChild(link);
                lista.appendChild(item);
            });
            corpoEl.appendChild(lista);
        }
    }
    /** Formata um tamanho em bytes como "12 KB"/"3.4 MB" pra exibir ao lado de cada anexo. */
    function formatarTamanhoDeArquivo(bytes) {
        if (bytes < 1024) {
            return bytes + ' B';
        }
        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
})();
