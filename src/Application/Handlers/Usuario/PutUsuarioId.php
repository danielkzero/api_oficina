<?php
//routes.php
namespace App\Application\Handlers\Usuario;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutUsuarioId
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
            $body = $request->getParsedBody();

            $senha = md5($body['senha']);
            $stmt = $this->pdo->prepare('UPDATE usuario SET 
                nome=:nome, 
                sobrenome=:sobrenome, 
                email=:email, 
                usuario=:usuario, 
                senha=:senha,
                ativo=:ativo
            WHERE id=:id');            
            $stmt->bindParam(':nome', $body['usuario']);
            $stmt->bindParam(':sobrenome', $body['usuario']);
            $stmt->bindParam(':email', $body['usuario']);
            $stmt->bindParam(':usuario', $body['nome']);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':ativo', $body['ativo']);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true]);

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