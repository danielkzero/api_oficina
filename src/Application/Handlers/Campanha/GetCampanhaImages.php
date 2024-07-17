<?php
// get-campaign-images.php
namespace App\Application\Handlers\Campanha;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetCampanhaImages
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'];

            // Buscar imagens associadas ao campaignId
            $stmt = $this->pdo->prepare('SELECT galeria_imagens FROM campanhas WHERE id = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($images);

        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
