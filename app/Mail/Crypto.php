<?php

namespace App\Mail;

use App\Db\Database;

/**
 * Criptografa/descriptografa o refresh token do Gmail de cada usuário
 * (App\Entity\Vaga::$google_refresh_token) usando AES-256-CBC com a
 * chave APP_KEY do .env.
 *
 * Diferente da senha de login (que usa password_hash e nunca é
 * recuperada), o refresh token precisa ser lido de volta em texto puro
 * pra trocar por um access token na API do Google — por isso
 * criptografia reversível, não hash.
 */
class Crypto{

    private const CIPHER = 'aes-256-cbc';

    /** Deriva a chave binária de 32 bytes a partir do APP_KEY (base64) do .env. */
    private static function key(){
        $chave = Database::env('APP_KEY', null);
        if (empty($chave)) {
            throw new \RuntimeException('APP_KEY não configurada no .env.');
        }
        return base64_decode($chave);
    }

    /** Criptografa $texto, devolvendo uma string base64 (IV + dados cifrados). */
    public static function encrypt($texto){
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $cifrado = openssl_encrypt($texto, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv.$cifrado);
    }

    /** Descriptografa uma string gerada por encrypt(). Devolve null se inválida. */
    public static function decrypt($base64){
        if (empty($base64)) {
            return null;
        }

        $dados = base64_decode($base64, true);
        if ($dados === false) {
            return null;
        }

        $tamanhoIv = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($dados, 0, $tamanhoIv);
        $cifrado = substr($dados, $tamanhoIv);

        $texto = openssl_decrypt($cifrado, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        return $texto !== false ? $texto : null;
    }
}
