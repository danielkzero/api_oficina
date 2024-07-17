<?php
//routes.php
namespace App\Application\Handlers\Usuario;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetUsuarioId
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $this->validarToken($request);
            $id = $args['id'];

            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id=:id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($result);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }

    private function validarToken(Request $request)
    {
        require_once __DIR__ . '/../../../Auth/validate.php';
        ValidarToken($request);
    }
}