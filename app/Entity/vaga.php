<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

class Vaga{
    public $id;
    public $nome;
    public $sobrenome;
    public $ativo;
    public $email;
    public $datanascimento;
    public $senha;
    
    
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
    public function atualizar(){
        return (new Database('rbextensionst'))->update('id = '.$this->id,[
                        'nome' => $this->nome,
                        'sobrenome' => $this->sobrenome,
                        'ativo' => $this->ativo,
                        'email' => $this->email,
                        'datanascimento' => $this->datanascimento,
                        'senha' => $this->senha
            ]);
    }
    public function excluir(){
            return (new Database('rbextensionst'))->delete('id = '.$this->id);
        }

    public static function getVagas($where = null, $order = null, $limit = null){
        return (new Database('rbextensionst'))->select($where,$order,$limit)
                                                ->fetchAll(PDO::FETCH_CLASS,self::class);
        }

    public static function getTotalVagas($where = null){
        return (new Database('rbextensionst'))->select($where, null, null, 'COUNT(*) as total')
                                                ->fetchObject()->total;
        }

    public static function getVaga($id){
        return (new Database('rbextensionst'))->select('id = '.$id)
                                                ->fetchObject(self::class);
        }
}