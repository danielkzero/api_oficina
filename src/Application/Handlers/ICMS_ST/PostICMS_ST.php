<?php
namespace App\Application\Handlers\ICMS_ST;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PostICMS_ST
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $stmt = $this->pdo->prepare("INSERT INTO icms_st (codigo_ncm, nome_excecao_fiscal, estado_destino, tipo_st, valor_mva, valor_pmc, icms_credito, icms_destino, preco_considerado_no_calculo, reducao_de_base, excluido, ultima_alteracao) VALUES (:codigo_ncm, :nome_excecao_fiscal, :estado_destino, :tipo_st, :valor_mva, :valor_pmc, :icms_credito, :icms_destino, :preco_considerado_no_calculo, :reducao_de_base, :excluido, NOW())");

        $stmt->bindParam(':codigo_ncm', $data['codigo_ncm']);
        $stmt->bindParam(':nome_excecao_fiscal', $data['nome_excecao_fiscal']);
        $stmt->bindParam(':estado_destino', $data['estado_destino']);
        $stmt->bindParam(':tipo_st', $data['tipo_st']);
        $stmt->bindParam(':valor_mva', $data['valor_mva']);
        $stmt->bindParam(':valor_pmc', $data['valor_pmc']);
        $stmt->bindParam(':icms_credito', $data['icms_credito']);
        $stmt->bindParam(':icms_destino', $data['icms_destino']);
        $stmt->bindParam(':excluido', $data['excluido']);

        if ($stmt->execute()) {
            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success']);
        } else {
            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
        }
    }
}
