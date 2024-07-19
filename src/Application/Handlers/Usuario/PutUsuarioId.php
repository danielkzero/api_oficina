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
            $id = $args['id'];
            $body = $request->getParsedBody();

            $nome = $body['nome'];
            $email = $body['email'];
            $usuario = $body['usuario'];
            $senha = md5($body['senha']);

            $stmt = $this->pdo->prepare('UPDATE usuarios SET 
                nome=:nome, 
                email=:email, 
                usuario=:usuario, 
                senha=:senha
            WHERE id=:id');            
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true]);

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }

}