<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

/**
 * Representa uma movimentação de estoque (tabela `movimentacoes_estoque`:
 * id, ProdutoId, Tipo [entrada/saida], Quantidade, Observacao, Data).
 *
 * ProdutoCodigo/ProdutoNome vêm de um JOIN com `produtos` (feito em
 * getMovimentacoes/getTotalMovimentacoes/getMovimentacao, direto via
 * Database::execute() — a tabela `produtos` não faz parte desta
 * entidade), só pra exibição; não são colunas desta tabela.
 *
 * Esta classe só insere/remove a movimentação em si. Ajustar a
 * Quantidade do produto correspondente (+ na entrada, - na saída) é
 * responsabilidade de quem chama (dashboard/estoque-criar.php e
 * dashboard/estoque-excluir.php), que já precisam validar estoque
 * suficiente antes de gravar.
 */
class MovimentacaoEstoque{
    public $id;
    public $ProdutoId;
    public $Tipo;
    public $Quantidade;
    public $Observacao;
    public $Data;
    public $ProdutoCodigo;
    public $ProdutoNome;

    /**
     * Busca movimentações (com o código/nome do produto via JOIN),
     * da mais recente pra mais antiga. $where deve usar placeholders
     * `?` com os campos das tabelas `m` (movimentacoes_estoque) e `p`
     * (produtos) — ex: 'p.Nome LIKE ?' ou 'm.Tipo = ?'.
     *
     * @return MovimentacaoEstoque[]
     */
    public static function getMovimentacoes($where = null, $params = [], $limit = null){
        $where = !empty($where) ? 'WHERE '.$where : '';
        $limit = !empty($limit) ? 'LIMIT '.$limit : '';

        $sql = 'SELECT m.*, p.Codigo AS ProdutoCodigo, p.Nome AS ProdutoNome
                FROM `movimentacoes_estoque` m
                INNER JOIN `produtos` p ON p.id = m.ProdutoId
                '.$where.'
                ORDER BY m.Data DESC, m.id DESC
                '.$limit;

        return (new Database())->execute($sql, $params)->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Conta quantas movimentações casam com $where (mesma regra de
     * placeholders/params de getMovimentacoes). Usado na paginação.
     */
    public static function getTotalMovimentacoes($where = null, $params = []){
        $where = !empty($where) ? 'WHERE '.$where : '';

        $sql = 'SELECT COUNT(*) as total
                FROM `movimentacoes_estoque` m
                INNER JOIN `produtos` p ON p.id = m.ProdutoId
                '.$where;

        return (new Database())->execute($sql, $params)->fetchObject()->total;
    }

    /** Busca uma única movimentação pelo id (com o produto via JOIN). Retorna null se não existir. */
    public static function getMovimentacao($id){
        $sql = 'SELECT m.*, p.Codigo AS ProdutoCodigo, p.Nome AS ProdutoNome
                FROM `movimentacoes_estoque` m
                INNER JOIN `produtos` p ON p.id = m.ProdutoId
                WHERE m.id = ?';

        return (new Database())->execute($sql, [$id])->fetchObject(self::class);
    }

    /**
     * Insere esta movimentação no banco, usando os valores já
     * atribuídos. Preenche $this->id com o id gerado. Não mexe na
     * Quantidade do produto — ver o aviso na doc da classe.
     */
    public function cadastrar(){
        $obDatabase = new Database('movimentacoes_estoque');
        $this->id = $obDatabase->insert([
            'ProdutoId' => $this->ProdutoId,
            'Tipo' => $this->Tipo,
            'Quantidade' => $this->Quantidade,
            'Observacao' => $this->Observacao,
            'Data' => $this->Data,
        ]);
        return true;
    }

    /**
     * Remove esta movimentação (identificada por $this->id) do banco.
     * Não mexe na Quantidade do produto — ver o aviso na doc da classe.
     */
    public function excluir(){
        return (new Database('movimentacoes_estoque'))->delete('id = ?', [$this->id]);
    }
}
