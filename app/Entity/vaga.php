<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

/**
 * Representa um usuário do sistema (tabela `rbextensionst`).
 *
 * O nome da classe ("Vaga") é um resquício do template original do
 * projeto — na prática ela modela um usuário (nome, email, senha etc.),
 * não uma vaga de emprego.
 */
class Vaga{
    public $id;
    public $nome;
    public $sobrenome;
    public $ativo;
    public $email;
    public $datanascimento;
    public $senha;
    public $foto;
    public $google_refresh_token;


    /**
     * Cria o usuário no banco a partir dos dados de $_POST, já
     * transformando a senha em texto puro num hash (password_hash)
     * antes de gravar.
     */
    public function cadastrar(){
    $this->datanascimento = $_POST['datanascimento'];
    $this->ativo = $_POST['ativo'] === 's' ? 's' : 'n';

    $this->senha = $_POST['senha'];
    $senhahash = password_hash($this->senha, PASSWORD_DEFAULT);
    $this->senha = $senhahash;

    $obDatabase = new Database('rbextensionst');
    $this->id = $obDatabase->insert([
                        'nome' => $this->nome,
                        'sobrenome' => $this->sobrenome,
                        'ativo' => $this->ativo,
                        'email' => $this->email,
                        'datanascimento' => $this->datanascimento,
                        'senha' => $this->senha
            ]);
        return true;
    }

    /**
     * Atualiza a linha deste usuário (identificada por $this->id) com
     * os valores atuais das propriedades do objeto.
     */
    public function atualizar(){
        return (new Database('rbextensionst'))->update('id = ?',[
                        'nome' => $this->nome,
                        'sobrenome' => $this->sobrenome,
                        'ativo' => $this->ativo,
                        'email' => $this->email,
                        'datanascimento' => $this->datanascimento,
                        'senha' => $this->senha,
                        'foto' => $this->foto
            ], [$this->id]);
    }

    /**
     * Grava o refresh token do Google (já criptografado por
     * App\Mail\Crypto) deste usuário, sem mexer nos demais campos.
     * Usado pela página de e-mail (dashboard/servicos.php) depois do
     * login OAuth (dashboard/servicos-google-callback.php) ou pra
     * "sair do e-mail" (passando null) — separado de atualizar()
     * porque esse valor não faz parte do formulário de cadastro/edição
     * de usuário.
     */
    public function salvarGoogleRefreshToken($refreshTokenCriptografado){
        $this->google_refresh_token = $refreshTokenCriptografado;
        return (new Database('rbextensionst'))->update('id = ?', [
                        'google_refresh_token' => $this->google_refresh_token,
            ], [$this->id]);
    }

    /** Remove este usuário (identificado por $this->id) do banco. */
    public function excluir(){
            return (new Database('rbextensionst'))->delete('id = ?', [$this->id]);
        }

    /**
     * Busca uma lista de usuários. $where deve usar placeholders `?`
     * (ex: 'email = ?'), com os valores em $params — nunca concatenar
     * valores direto na string, para não abrir brecha de SQL injection.
     *
     * @return Vaga[]
     */
    public static function getVagas($where = null, $order = null, $limit = null, $params = []){
        return (new Database('rbextensionst'))->select($where,$order,$limit,'*',$params)
                                                ->fetchAll(PDO::FETCH_CLASS,self::class);
        }

    /**
     * Conta quantos usuários casam com $where (mesma regra de
     * placeholders/params de getVagas). Usado para calcular a paginação.
     */
    public static function getTotalVagas($where = null, $params = []){
        return (new Database('rbextensionst'))->select($where, null, null, 'COUNT(*) as total', $params)
                                                ->fetchObject()->total;
        }

    /** Busca um único usuário pelo id. Retorna null se não existir. */
    public static function getVaga($id){
        return (new Database('rbextensionst'))->select('id = ?', null, null, '*', [$id])
                                                ->fetchObject(self::class);
        }
}
