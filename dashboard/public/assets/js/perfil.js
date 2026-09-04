"use strict";
/**
 * Perfil (dashboard/perfil.php): o card "Meus Dados" nasce travado
 * (campos com `disabled`, e os botões "Alterar Foto"/"Remover Foto"
 * escondidos — todos com a classe .campo-editavel) — "Editar Dados"
 * libera/revela tudo isso e troca pelo botão "Salvar Alterações", que
 * só existe visível a partir daqui. Se o envio falhar (ex: e-mail
 * inválido), o servidor já manda a página de volta destravada (ver
 * $modoEdicaoDeDados em perfil.php), então não depende deste script
 * pra isso — só a primeira transição (ver → editar) é feita aqui.
 *
 * "Alterar Foto"/"Remover Foto" são botões que só existem pra
 * disparar/alternar os inputs de verdade (#input-foto, escondido, e
 * #input-remover-foto, um checkbox escondido) — o que de fato vai no
 * POST continua sendo esses inputs.
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/perfil.js.
 */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        ligarEdicaoDeDados();
        ligarBotoesDeFoto();
    });
    /** "Editar Dados": libera (disabled = false) e revela (tira "escondido") tudo que for .campo-editavel, e troca os botões de ação. */
    function ligarEdicaoDeDados() {
        const botaoEditar = document.getElementById('botao-editar-dados');
        const botaoSalvar = document.getElementById('botao-salvar-dados');
        const form = document.getElementById('form-meus-dados');
        if (!botaoEditar || !botaoSalvar || !form) {
            return;
        }
        botaoEditar.addEventListener('click', function () {
            form.querySelectorAll('.campo-editavel').forEach(function (campo) {
                campo.disabled = false;
                campo.classList.remove('escondido');
            });
            botaoEditar.classList.add('escondido');
            botaoSalvar.classList.remove('escondido');
        });
    }
    /** "Alterar Foto" abre o seletor de arquivo escondido; "Remover Foto" alterna o checkbox escondido (e desfaz sozinho se uma foto nova for escolhida depois). */
    function ligarBotoesDeFoto() {
        const botaoAlterarFoto = document.getElementById('botao-alterar-foto');
        const inputFoto = document.getElementById('input-foto');
        const nomeArquivoEl = document.getElementById('nome-arquivo-foto');
        const botaoRemoverFoto = document.getElementById('botao-remover-foto');
        const inputRemoverFoto = document.getElementById('input-remover-foto');
        if (botaoAlterarFoto && inputFoto) {
            botaoAlterarFoto.addEventListener('click', function () {
                inputFoto.click();
            });
            inputFoto.addEventListener('change', function () {
                const arquivoEscolhido = inputFoto.files && inputFoto.files[0];
                if (nomeArquivoEl) {
                    nomeArquivoEl.textContent = arquivoEscolhido ? arquivoEscolhido.name : '';
                }
                if (arquivoEscolhido && inputRemoverFoto && inputRemoverFoto.checked) {
                    inputRemoverFoto.checked = false;
                    if (botaoRemoverFoto) {
                        atualizarBotaoRemoverFoto(botaoRemoverFoto, false);
                    }
                }
            });
        }
        if (botaoRemoverFoto && inputRemoverFoto) {
            botaoRemoverFoto.addEventListener('click', function () {
                inputRemoverFoto.checked = !inputRemoverFoto.checked;
                atualizarBotaoRemoverFoto(botaoRemoverFoto, inputRemoverFoto.checked);
            });
        }
    }
    function atualizarBotaoRemoverFoto(botao, marcadoParaRemover) {
        botao.textContent = marcadoParaRemover ? 'Cancelar Remoção' : 'Remover Foto';
        botao.classList.toggle('ativo', marcadoParaRemover);
    }
})();
