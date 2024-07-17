<?php
// delete-image.php
namespace App\Application\Handlers\Campanha;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class DeleteCampanhaImages
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $body = $request->getParsedBody();
            $id = $args['id'];
            $filePath = $body['path'];

            // Verifica se o arquivo existe
            if (file_exists($filePath)) {
                // Obtém a lista atual de imagens da campanha
                $stmt = $this->pdo->prepare('SELECT galeria_imagens FROM campanhas WHERE id=:id');
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $galleryImages = json_decode($result['galeria_imagens'], true);

                    // Remove o caminho da imagem do array
                    if (($key = array_search($filePath, $galleryImages)) !== false) {
                        unset($galleryImages[$key]);
                    }

                    // Atualiza a lista de imagens no banco de dados
                    $updatedGalleryImages = json_encode(array_values($galleryImages));
                    $stmt = $this->pdo->prepare('UPDATE campanhas SET galeria_imagens=:galeria_imagens WHERE id=:id');
                    $stmt->bindParam(':id', $id);
                    $stmt->bindParam(':galeria_imagens', $updatedGalleryImages);
                    $stmt->execute();

                    // Remove o arquivo do sistema de arquivos
                    unlink($filePath);

                    return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true]);
                } else {
                    throw new Exception('Campanha não encontrada.', 404);
                }
            } else {
                throw new Exception('Imagem não encontrada.', 404);
            }
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
