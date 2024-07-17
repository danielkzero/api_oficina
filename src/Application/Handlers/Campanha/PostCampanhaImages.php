<?php
//routes.php
namespace App\Application\Handlers\Campanha;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostCampanhaImages
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            // Recupera o ID da campanha dos argumentos da requisição
            $id = $args['id'];
           
            // Verifica se a requisição contém arquivos
            $uploadedFiles = $request->getUploadedFiles();            

            if (!empty($uploadedFiles)) {
                // Pasta onde as imagens serão salvas
                $uploadDir = 'path/';
                
                // Obtém a lista atual de imagens da campanha
                $stmt = $this->pdo->prepare('SELECT galeria_imagens FROM campanhas WHERE id=:id');
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                // Inicializa o array de imagens da galeria
                $galleryImages = array();
                if ($result && !empty($result['galeria_imagens'])) {
                    $galleryImages = json_decode($result['galeria_imagens'], true);
                }

                // Upload das imagens
                foreach ($uploadedFiles as $uploadedFile) {
                    // Verifica se é um arquivo válido
                    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                        // Gera um nome único para o arquivo mantendo a extensão original
                        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                        $uniqueFilename = uniqid() . '.' . $extension;
                        $filepath = $uploadDir . $uniqueFilename;

                        // Move o arquivo para o diretório de upload
                        $uploadedFile->moveTo($filepath);
                        
                        // Adiciona o caminho do arquivo ao array de imagens da galeria
                        $galleryImages[] = $filepath;                        
                    }
                }

                // Atualiza o campo galeria_imagens no banco de dados
                $updatedGalleryImages = json_encode(array_values($galleryImages));
                $stmt = $this->pdo->prepare('UPDATE campanhas SET galeria_imagens=:galeria_imagens WHERE id=:id');
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':galeria_imagens', $updatedGalleryImages);
                $stmt->execute();

                return $response->withHeader('Content-Type', 'application/json')->withJson(
                    [
                        'success' => true
                    ]
                );
            } else {
                throw new Exception('Nenhuma imagem foi enviada.', 400);
            }
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
