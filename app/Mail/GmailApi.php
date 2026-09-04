<?php

namespace App\Mail;

/**
 * Fala direto com a API REST do Gmail (HTTPS/443 — funciona mesmo em
 * redes que bloqueiam as portas de SMTP/IMAP puro, como costuma
 * acontecer em rede corporativa). Recebe um access token já pronto
 * (App\Mail\GoogleOAuth::obterAccessToken) e não sabe nada sobre
 * refresh token nem sobre qual usuário está logado.
 */
class GmailApi{

    private const BASE = 'https://gmail.googleapis.com/gmail/v1/users/me';

    /**
     * Pastas lógicas usadas nas páginas -> label fixa do Gmail. Essas
     * labels de sistema têm o mesmo id em qualquer idioma da conta
     * (diferente de nomes de pasta IMAP, que mudam de nome conforme o
     * idioma).
     */
    private const LABELS = [
        'caixa' => 'INBOX',
        'enviados' => 'SENT',
        'rascunhos' => 'DRAFT',
        'lixeira' => 'TRASH',
    ];

    private $accessToken;

    public function __construct($accessToken){
        $this->accessToken = $accessToken;
    }

    /**
     * Envia um e-mail pra $destinatario, em nome da própria conta
     * autenticada ("me"). $anexos (opcional) é uma lista de
     * ['nome' => ..., 'tipo' => mimetype, 'conteudo' => bytes brutos do
     * arquivo] — qualquer tipo de arquivo, o Gmail não restringe.
     */
    public function enviar($destinatario, $assunto, $mensagem, array $anexos = []){
        $this->chamar('POST', '/messages/send', ['raw' => $this->montarMensagemBruta($destinatario, $assunto, $mensagem, $anexos)]);
    }

    private function montarMensagemBruta($destinatario, $assunto, $mensagem, array $anexos = []){
        $cabecalhosComuns = [
            'To: '.$destinatario,
            'Subject: =?UTF-8?B?'.base64_encode($assunto).'?=',
            'MIME-Version: 1.0',
        ];

        if (empty($anexos)) {
            $cabecalhos = array_merge($cabecalhosComuns, [
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ]);
            $bruto = implode("\r\n", $cabecalhos)."\r\n\r\n".base64_encode($mensagem);
            return $this->base64urlCodificar($bruto);
        }

        $boundary = 'limite_'.bin2hex(random_bytes(16));

        $partes = ["--{$boundary}\r\n".
            "Content-Type: text/plain; charset=UTF-8\r\n".
            "Content-Transfer-Encoding: base64\r\n\r\n".
            base64_encode($mensagem)];

        foreach ($anexos as $anexo) {
            $nome = str_replace(["\r", "\n", '"'], '', $anexo['nome']);
            $tipo = preg_match('#^[\w.+-]+/[\w.+-]+$#', $anexo['tipo']) ? $anexo['tipo'] : 'application/octet-stream';

            $partes[] = "--{$boundary}\r\n".
                "Content-Type: {$tipo}; name=\"{$nome}\"\r\n".
                "Content-Disposition: attachment; filename=\"{$nome}\"\r\n".
                "Content-Transfer-Encoding: base64\r\n\r\n".
                chunk_split(base64_encode($anexo['conteudo']));
        }

        $cabecalhos = array_merge($cabecalhosComuns, ["Content-Type: multipart/mixed; boundary=\"{$boundary}\""]);
        $bruto = implode("\r\n", $cabecalhos)."\r\n\r\n".implode("\r\n\r\n", $partes)."\r\n--{$boundary}--";

        return $this->base64urlCodificar($bruto);
    }

    /**
     * Lista mensagens de uma pasta lógica. A API do Gmail não suporta
     * "ir pra página N" — só "próxima página", via um token opaco
     * (nextPageToken). $pageToken null busca a primeira página.
     *
     * $dataInicio/$dataFim (strings "Y-m-d", opcionais) viram os
     * operadores de busca after:/before: do Gmail — só $dataInicio
     * filtra por aquele dia; os dois juntos filtram o período
     * (inclusive nas duas pontas). $busca (opcional) filtra por
     * remetente OU assunto contendo o termo.
     *
     * @return array{mensagens: array, proximoPageToken: ?string}
     * @throws \RuntimeException se a pasta for inválida ou a chamada falhar
     */
    public function listar($pastaChave, $pageToken = null, $porPagina = 10, $dataInicio = null, $dataFim = null, $busca = null){
        if (!isset(self::LABELS[$pastaChave])) {
            throw new \RuntimeException('Pasta inválida.');
        }

        $parametros = [
            'labelIds' => self::LABELS[$pastaChave],
            'maxResults' => $porPagina,
        ];
        if (!empty($pageToken)) {
            $parametros['pageToken'] = $pageToken;
        }

        $filtro = $this->montarFiltro($dataInicio, $dataFim, $busca);
        if ($filtro !== '') {
            $parametros['q'] = $filtro;
        }

        $lista = $this->chamar('GET', '/messages?'.http_build_query($parametros));

        $mensagens = [];
        foreach (($lista['messages'] ?? []) as $referencia) {
            $mensagens[] = $this->buscarResumo($referencia['id']);
        }

        return [
            'mensagens' => $mensagens,
            'proximoPageToken' => $lista['nextPageToken'] ?? null,
        ];
    }

    /**
     * Busca o corpo completo de uma mensagem pelo id (o mesmo id devolvido
     * por listar()). Quando a mensagem tem versão HTML, devolve ela
     * (sanitizada, com as imagens embutidas via cid: já baixadas e
     * convertidas em data:) em "corpoHtml"; senão devolve texto simples
     * em "corpo". "anexos" lista os arquivos de verdade anexados
     * (baixáveis via servicos-inbox-anexo.php), sem contar as imagens
     * inline do próprio template do e-mail.
     */
    public function ler($id){
        $mensagem = $this->chamar('GET', '/messages/'.rawurlencode($id).'?format=full');
        $payload = $mensagem['payload'] ?? [];
        $cabecalhos = $this->indexarCabecalhos($payload['headers'] ?? []);

        $anexos = [];
        $imagensInline = [];
        $this->coletarPartes($payload, $anexos, $imagensInline);

        $corpoHtml = $this->montarCorpoHtml($id, $payload, $imagensInline);

        return [
            'uid' => $id,
            'de' => $cabecalhos['from'] ?? '',
            'assunto' => $cabecalhos['subject'] ?? '(sem assunto)',
            'data' => $this->formatarData($cabecalhos['date'] ?? null),
            'corpoHtml' => $corpoHtml,
            'corpo' => $corpoHtml === null ? $this->extrairCorpo($payload) : null,
            'anexos' => $anexos,
        ];
    }

    private function buscarResumo($id){
        $query = http_build_query(['format' => 'metadata']).'&metadataHeaders=Subject&metadataHeaders=From&metadataHeaders=Date';
        $mensagem = $this->chamar('GET', '/messages/'.rawurlencode($id).'?'.$query);
        $cabecalhos = $this->indexarCabecalhos($mensagem['payload']['headers'] ?? []);

        return [
            'uid' => $id,
            'de' => $cabecalhos['from'] ?? '',
            'assunto' => $cabecalhos['subject'] ?? '(sem assunto)',
            'data' => $this->formatarData($cabecalhos['date'] ?? null),
            'lida' => !in_array('UNREAD', $mensagem['labelIds'] ?? [], true),
        ];
    }

    /** Combina o filtro de período com o de busca (remetente/assunto) num único "q" (Gmail entende termos separados por espaço como E). */
    private function montarFiltro($dataInicio, $dataFim, $busca){
        $partes = [];

        if (!empty($dataInicio) && !empty($dataFim)) {
            $depois = date('Y/m/d', strtotime($dataInicio));
            $antes = date('Y/m/d', strtotime($dataFim.' +1 day'));
            $partes[] = "after:{$depois} before:{$antes}";
        } elseif (!empty($dataInicio)) {
            $depois = date('Y/m/d', strtotime($dataInicio));
            $antes = date('Y/m/d', strtotime($dataInicio.' +1 day'));
            $partes[] = "after:{$depois} before:{$antes}";
        }

        if (!empty($busca)) {
            $termo = str_replace('"', '', trim($busca));
            $partes[] = "(from:\"{$termo}\" OR subject:\"{$termo}\")";
        }

        return implode(' ', $partes);
    }

    private function formatarData($valorCabecalhoDate){
        if (empty($valorCabecalhoDate)) {
            return '';
        }
        $timestamp = strtotime($valorCabecalhoDate);
        return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '';
    }

    private function indexarCabecalhos($headers){
        $indexado = [];
        foreach ($headers as $cabecalho) {
            $decodificado = @iconv_mime_decode($cabecalho['value'], 0, 'UTF-8');
            $indexado[strtolower($cabecalho['name'])] = $decodificado !== false ? $decodificado : $cabecalho['value'];
        }
        return $indexado;
    }

    /** Percorre o payload MIME (recursivo) procurando text/plain; sem isso, cai pro text/html sem as tags. */
    private function extrairCorpo($payload){
        $textoPlano = $this->procurarParte($payload, 'text/plain');
        if ($textoPlano !== null) {
            return $textoPlano;
        }

        $html = $this->procurarParte($payload, 'text/html');
        return $html !== null ? $this->htmlParaTexto($html) : '';
    }

    /**
     * Converte HTML de e-mail em texto simples: remove <style>/<script>
     * inteiros (senão o CSS/JS interno sobra como "código" no texto,
     * já que strip_tags só tira as tags, não o conteúdo delas), troca
     * quebras de bloco por \n pra manter parágrafos legíveis, e só
     * então decodifica entidades (&nbsp;, &amp;, ...).
     */
    private function htmlParaTexto($html){
        $html = preg_replace('#<(style|script)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(br|/p|/div|/tr|/li)\b[^>]*>#i', "\n", $html);
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", $texto));
    }

    private function procurarParte($parte, $mimeTypeAlvo){
        if (($parte['mimeType'] ?? '') === $mimeTypeAlvo && !empty($parte['body']['data'])) {
            return $this->base64urlDecodificar($parte['body']['data']);
        }

        foreach (($parte['parts'] ?? []) as $subParte) {
            $encontrado = $this->procurarParte($subParte, $mimeTypeAlvo);
            if ($encontrado !== null) {
                return $encontrado;
            }
        }

        return null;
    }

    /**
     * Percorre o payload MIME (recursivo) separando, entre as partes com
     * arquivo (têm "filename"): as com Content-Disposition: inline (logo
     * do template, referenciada no HTML via cid:) em $imagensInline
     * (indexadas pelo Content-ID, sem os "<...>"), e as demais em
     * $anexos — essas sim são arquivo de verdade que a pessoa anexou,
     * pra listar com opção de baixar.
     */
    private function coletarPartes($parte, array &$anexos, array &$imagensInline){
        $attachmentId = $parte['body']['attachmentId'] ?? null;

        if (!empty($parte['filename']) && $attachmentId) {
            $cabecalhosParte = $this->indexarCabecalhos($parte['headers'] ?? []);
            $contentId = trim($cabecalhosParte['content-id'] ?? '', "<> \t\n\r\0\x0B");
            $inline = $contentId !== '' && stripos($cabecalhosParte['content-disposition'] ?? '', 'inline') !== false;

            if ($inline) {
                $imagensInline[$contentId] = [
                    'attachmentId' => $attachmentId,
                    'mimeType' => $parte['mimeType'] ?? 'application/octet-stream',
                ];
            } else {
                $anexos[] = [
                    'nome' => $parte['filename'],
                    'tipo' => $parte['mimeType'] ?? 'application/octet-stream',
                    'tamanho' => $parte['body']['size'] ?? 0,
                    'attachmentId' => $attachmentId,
                ];
            }
        }

        foreach (($parte['parts'] ?? []) as $subParte) {
            $this->coletarPartes($subParte, $anexos, $imagensInline);
        }
    }

    /**
     * Monta o HTML pra exibir: pega a parte text/html (null se a
     * mensagem não tiver uma), troca cada "cid:..." pela imagem
     * correspondente já em data: (baixando cada uma via
     * buscarAnexo — só as que o próprio HTML referencia, não a lista
     * inteira de $imagensInline) e sanitiza antes de devolver.
     */
    private function montarCorpoHtml($messageId, $payload, array $imagensInline){
        $html = $this->procurarParte($payload, 'text/html');
        if ($html === null) {
            return null;
        }

        if (!empty($imagensInline)) {
            $html = preg_replace_callback('/cid:([^"\'\s)]+)/i', function ($correspondencia) use ($messageId, $imagensInline) {
                $info = $imagensInline[$correspondencia[1]] ?? null;
                if ($info === null) {
                    return $correspondencia[0];
                }
                try {
                    $bytes = $this->buscarAnexo($messageId, $info['attachmentId']);
                } catch (\Throwable $e) {
                    return $correspondencia[0];
                }
                return 'data:'.$info['mimeType'].';base64,'.base64_encode($bytes);
            }, $html);
        }

        return $this->sanitizarHtml($html);
    }

    /**
     * Baixa o conteúdo bruto (bytes, já decodificado) de um anexo pelo
     * attachmentId devolvido em "anexos"/imagens inline de ler(). Usado
     * tanto pra embutir imagens inline quanto por
     * dashboard/servicos-inbox-anexo.php pra oferecer o download.
     *
     * @throws \RuntimeException se o Google recusar a chamada
     */
    public function buscarAnexo($messageId, $attachmentId){
        $dados = $this->chamar('GET', '/messages/'.rawurlencode($messageId).'/attachments/'.rawurlencode($attachmentId));
        return $this->base64urlDecodificar($dados['data'] ?? '');
    }

    /**
     * Sanitiza o HTML do e-mail antes de mandar pro navegador: remove
     * de vez tags perigosas/inúteis num e-mail (script, iframe, object,
     * embed, form, meta, base, link, applet) e qualquer atributo
     * on*=/href|src="javascript:..." que sobrar. O restante (inclusive
     * <style>, já que aqui o HTML é isolado num iframe sandbox no
     * front, não indo pro DOM da página) fica intacto.
     */
    private function sanitizarHtml($html){
        $documento = new \DOMDocument();
        libxml_use_internal_errors(true);
        $documento->loadHTML(
            '<?xml encoding="utf-8" ?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        foreach (['script', 'iframe', 'object', 'embed', 'form', 'meta', 'base', 'link', 'applet'] as $tag) {
            $elementos = $documento->getElementsByTagName($tag);
            for ($i = $elementos->length - 1; $i >= 0; $i--) {
                $elemento = $elementos->item($i);
                $elemento->parentNode->removeChild($elemento);
            }
        }

        $xpath = new \DOMXPath($documento);
        foreach ($xpath->query('//*[@*]') as $elemento) {
            foreach (iterator_to_array($elemento->attributes) as $atributo) {
                $nome = strtolower($atributo->nodeName);
                $valor = trim($atributo->nodeValue);
                $eUrlPerigosa = in_array($nome, ['href', 'src', 'action'], true) && stripos($valor, 'javascript:') === 0;
                if (strpos($nome, 'on') === 0 || $eUrlPerigosa) {
                    $elemento->removeAttribute($atributo->nodeName);
                }
            }
        }

        $raiz = $documento->getElementsByTagName('div')->item(0);
        $htmlLimpo = '';
        foreach (iterator_to_array($raiz->childNodes) as $filho) {
            $htmlLimpo .= $documento->saveHTML($filho);
        }
        return $htmlLimpo;
    }

    private function base64urlCodificar($dados){
        return rtrim(strtr(base64_encode($dados), '+/', '-_'), '=');
    }

    private function base64urlDecodificar($dados){
        return base64_decode(strtr($dados, '-_', '+/'));
    }

    /** @throws \RuntimeException se a chamada falhar (rede ou erro devolvido pela API) */
    private function chamar($metodo, $caminho, $corpo = null){
        $headers = ['Authorization: Bearer '.$this->accessToken];

        $opcoes = [
            CURLOPT_URL => self::BASE.$caminho,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => $metodo,
        ];

        if ($corpo !== null) {
            $headers[] = 'Content-Type: application/json';
            $opcoes[CURLOPT_POSTFIELDS] = json_encode($corpo);
        }

        $opcoes[CURLOPT_HTTPHEADER] = $headers;

        $ch = curl_init();
        curl_setopt_array($ch, $opcoes);
        $resposta = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($resposta === false) {
            throw new \RuntimeException('Falha de rede ao falar com o Gmail: '.$erroCurl);
        }

        $dados = json_decode($resposta, true) ?? [];

        if ($codigo >= 400) {
            throw new \RuntimeException($dados['error']['message'] ?? 'Erro ao falar com o Gmail.');
        }

        return $dados;
    }
}
