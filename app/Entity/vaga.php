<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

class Vaga{
    public $id;
    public $nome;
    public $sobrenome;
    public $email;
    public $datanascimento;
    public $senha;
    
    public function cadastrar(){
    $this->datanascimento = $_POST['datanascimento'];
    $this->senha = $_POST['senha'];
        
    $senhahash = password_hash($this->senha, PASSWORD_DEFAULT);
    $this->senha = $senhahash;
    $obDatabase = new Database('rbextensionst');
    $this->id = $obDatabase->insert([
                        'nome' => $this->nome,
                        'sobrenome' => $this->sobrenome,
                        'email' => $this->email,
                        'datanascimento' => $this->datanascimento,
                        'senha' => $this->senha
            ]);
        return true;    
    }
    public static function getVagas($where = null, $order = null, $limit = null){
        return (new Database('rbextensionst'))->select($where,$order,$limit)
                                            ->fetchAll(PDO::FETCH_CLASS,self::class);
    }
}