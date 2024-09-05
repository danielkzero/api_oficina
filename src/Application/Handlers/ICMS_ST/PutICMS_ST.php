<?php
namespace App\Application\Handlers\ICMS_ST;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutICMS_ST
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

        try {
            // Update ICMS_ST
            $stmt = $this->pdo->prepare("
                UPDATE icms_st 
                SET codigo_ncm = :codigo_ncm, nome_excecao_fiscal = :nome_excecao_fiscal, estado_destino = :estado_destino, tipo_st = :tipo_st, valor_mva = :valor_mva, valor_pmc = :valor_pmc, icms_credito = :icms_credito, icms_destino = :icms_destino, excluido = :excluido, ultima_alteracao = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':codigo_ncm' => $data['codigo_ncm'],
                ':nome_excecao_fiscal' => $data['nome_excecao_fiscal'],
                ':estado_destino' => $data['estado_destino'],
                ':tipo_st' => $data['tipo_st'],
                ':valor_mva' => $data['valor_mva'],
                ':valor_pmc' => $data['valor_pmc'],
                ':icms_credito' => $data['icms_credito'],
                ':icms_destino' => $data['icms_destino'],
                ':excluido' => $data['excluido']
            ]);

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'ICMS_ST atualizado com sucesso']);

        } catch (\Exception $e) {
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao atualizar ICMS_ST', 'error' => $e->getMessage()]);
        }
    }
}
