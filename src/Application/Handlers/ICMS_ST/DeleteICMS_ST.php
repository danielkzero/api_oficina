<?php
namespace App\Application\Handlers\ICMS_ST;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class DeleteICMS_ST
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        try {
            // Delete ICMS_ST
            $stmt = $this->pdo->prepare("UPDATE icms_st SET excluido = 1, ultima_alteracao = NOW() WHERE id = :id");
            $stmt->execute([':id' => $id]);

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'ICMS_ST deletado com sucesso']);

        } catch (\Exception $e) {
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao deletar ICMS_ST', 'error' => $e->getMessage()]);
        }
    }
}
