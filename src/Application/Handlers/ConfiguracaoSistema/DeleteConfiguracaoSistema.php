<?php
namespace App\Application\Handlers\ConfiguracaoSistema;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class DeleteConfiguracaoSistema 
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = $args['id']; // ID da configuração a ser removida

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM configuracao_sistema WHERE id = :id ORDER BY id DESC");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $response->withJson(['message' => 'Configuração removida com sucesso.']);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
