<?php
namespace App\Application\Handlers\Produto;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutProduto
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        $stmt = $this->pdo->prepare("UPDATE produto SET ipi = :ipi, preco_tabela = :preco_tabela, preco_minimo = :preco_minimo, nome = :nome, observacoes = :observacoes, saldo_estoque = :saldo_estoque, st = :st, ultima_alteracao = :ultima_alteracao, categoria_id = :categoria_id, comissao = :comissao, unidade = :unidade, codigo = :codigo, ativo = :ativo, codigo_ncm = :codigo_ncm, multiplo = :multiplo, peso_bruto = :peso_bruto, largura = :largura, altura = :altura, comprimento = :comprimento, excluido = :excluido WHERE id = :id");

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
            ':excluido' => $data['excluido'],
            ':id' => $id
        ]);

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Produto atualizado com sucesso']);
    }
}
