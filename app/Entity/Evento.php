<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

/**
 * Representa um evento da agenda (tabela `eventos`: id, Titulo,
 * Inicio, Fim).
 */
class Evento{
    public $id;
    public $Titulo;
    public $Inicio;
    public $Fim;

    /**
     * Busca eventos, ordenados por data de início (do mais antigo
     * para o mais recente). $where deve usar placeholders `?` (ex:
     * 'Titulo LIKE ?'), com os valores em $params — mesma regra de
     * Vaga::getVagas(), pra nunca concatenar valor de usuário direto
     * na query.
     *
     * @return Evento[]
     */
    public static function getEventos($where = null, $params = []){
        return (new Database('eventos'))->select($where, 'Inicio ASC', null, '*', $params)
                                         ->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Insere este evento no banco, usando os valores já atribuídos a
     * Titulo/Inicio/Fim. Preenche $this->id com o id gerado.
     */
    public function cadastrar(){
        $obDatabase = new Database('eventos');
        $this->id = $obDatabase->insert([
            'Titulo' => $this->Titulo,
            'Inicio' => $this->Inicio,
            'Fim' => $this->Fim,
        ]);
        return true;
    }

    /**
     * Atualiza este evento (identificado por $this->id) com os
     * valores atuais de Titulo/Inicio/Fim.
     */
    public function atualizar(){
        return (new Database('eventos'))->update('id = ?', [
            'Titulo' => $this->Titulo,
            'Inicio' => $this->Inicio,
            'Fim' => $this->Fim,
        ], [$this->id]);
    }

    /** Remove este evento (identificado por $this->id) do banco. */
    public function excluir(){
        return (new Database('eventos'))->delete('id = ?', [$this->id]);
    }
}
