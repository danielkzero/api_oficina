<?php
namespace App\Application\Handlers\Produto;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PostProduto
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $stmt = $this->pdo->prepare("INSERT INTO produto (ipi, preco_tabela, preco_minimo, nome, observacoes, saldo_estoque, st, ultima_alteracao, categoria_id, comissao, unidade, codigo, ativo, codigo_ncm, multiplo, peso_bruto, largura, altura, comprimento, excluido) VALUES (:ipi, :preco_tabela, :preco_minimo, :nome, :observacoes, :saldo_estoque, :st, :ultima_alteracao, :categoria_id, :comissao, :unidade, :codigo, :ativo, :codigo_ncm, :multiplo, :peso_bruto, :largura, :altura, :comprimento, :excluido)");

        $stmt->execute([
            ':ipi' => $data['ipi'],
            ':preco_tabela' => $data['preco_tabela'],
            ':preco_minimo' => $data['preco_minimo'],
            ':nome' => $data['nome'],
            ':observacoes' => $data['observacoes'] ?? null,
            ':saldo_estoque' => $data['saldo_estoque'],
            ':st' => $data['st'],
            ':ultima_alteracao' => $data['ultima_alteracao'],
            ':categoria_id' => $data['categoria_id'],
            ':comissao' => $data['comissao'],
            ':unidade' => $data['unidade'],
            ':codigo' => $data['codigo'],
            ':ativo' => $data['ativo'],
            ':codigo_ncm' => $data['codigo_ncm'],
            ':multiplo' => $data['multiplo'],
            ':peso_bruto' => $data['peso_bruto'],
            ':largura' => $data['largura'],
            ':altura' => $data['altura'],
            ':comprimento' => $data['comprimento'],
            ':excluido' => $data['excluido']
        ]);

        $id = $this->pdo->lastInsertId();

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Produto criado com sucesso', 'id' => $id]);
    }
}
