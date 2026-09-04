<?php

namespace App\Mail;

use App\Db\Database;

/**
 * Fluxo OAuth2 do Google (Authorization Code, com refresh token) usado
 * pra autorizar o envio (Gmail API) em nome do e-mail do usuário
 * logado, sem guardar senha nenhuma — só um token que o próprio Google
 * emite e pode revogar a qualquer momento.
 *
 * Credenciais do app (criadas no Google Cloud Console) vêm do .env:
 * GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI.
 */
class GoogleOAuth{

    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    /** gmail.send pra enviar; gmail.readonly pra ler Caixa de Entrada/Enviados/Rascunhos/Lixeira. */
    private const SCOPES = 'https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.readonly';

    private static function clientId(){
        return Database::env('GOOGLE_CLIENT_ID', null);
    }

    private static function clientSecret(){
        return Database::env('GOOGLE_CLIENT_SECRET', null);
    }

    private static function redirectUri(){
        return Database::env('GOOGLE_REDIRECT_URI', null);
    }

    /**
     * Monta a URL de consentimento do Google. $state é conferido de
     * volta em servicos-google-callback.php (protege contra CSRF no
     * redirecionamento). access_type=offline + prompt=consent
     * garantem que o Google sempre devolva um refresh_token — sem
     * prompt=consent, ele só vem no primeiro consentimento.
     */
    public static function urlDeAutorizacao($state){
        $parametros = [
            'client_id' => self::clientId(),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];
        return self::AUTH_URL.'?'.http_build_query($parametros);
    }

    /**
     * Troca o "code" recebido no callback por tokens. Devolve o array
     * decodificado do Google (access_token, refresh_token, expires_in, ...).
     *
     * @throws \RuntimeException se o Google recusar a troca
     */
    public static function trocarCodigoPorTokens($code){
        return self::chamarTokenEndpoint([
            'code' => $code,
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Usa o refresh token salvo pra pedir um access token novo (eles
     * expiram em ~1h). Chamado a cada ação (enviar/listar/ler) — mais
     * simples que cachear o access token, e o custo extra é uma
     * chamada HTTPS rápida ao Google.
     *
     * @throws \RuntimeException se o Google recusar (ex: acesso revogado)
     */
    public static function obterAccessToken($refreshToken){
        $resposta = self::chamarTokenEndpoint([
            'refresh_token' => $refreshToken,
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'grant_type' => 'refresh_token',
        ]);
        return $resposta['access_token'];
    }

    /**
     * Descriptografa o refresh token salvo do usuário (App\Entity\Vaga::$google_refresh_token)
     * e troca por um access token novo. Usado pelos endpoints de
     * e-mail antes de qualquer chamada à API do Gmail.
     *
     * @throws \RuntimeException se não houver conta conectada, ou se o Google recusar
     */
    public static function obterAccessTokenParaUsuario($refreshTokenCriptografado){
        $refreshToken = Crypto::decrypt($refreshTokenCriptografado);
        if ($refreshToken === null) {
            throw new \RuntimeException('Conecte sua conta do Gmail antes de continuar.');
        }
        return self::obterAccessToken($refreshToken);
    }

    /** Revoga um token (access ou refresh) no Google. Falha em silêncio — só usado pra "Sair do E-mail". */
    public static function revogar($token){
        $ch = curl_init(self::REVOKE_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['token' => $token]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private static function chamarTokenEndpoint($campos){
        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($campos),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $corpo = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($corpo === false) {
            throw new \RuntimeException('Falha de rede ao falar com o Google: '.$erroCurl);
        }

        $dados = json_decode($corpo, true) ?? [];

        if ($codigo >= 400) {
            throw new \RuntimeException($dados['error_description'] ?? ($dados['error'] ?? 'Erro ao autenticar com o Google.'));
        }

        return $dados;
    }
}
