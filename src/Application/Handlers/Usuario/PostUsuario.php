<?php
//routes.php
namespace App\Application\Handlers\Usuario;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostUsuario
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
            $senha = md5($body['senha']);
            
            $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome, sobrenome, email, usuario, senha) VALUES (nome, sobrenome, email, usuario, senha)');
            $stmt->bindParam(':nome', $body['nome']);
            $stmt->bindParam(':sobrenome', $body['sobrenome']);
            $stmt->bindParam(':email', $body['email']);
            $stmt->bindParam(':usuario', $body['usuario']);
            $stmt->bindParam(':senha', $senha);
            $stmt->execute();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true]);

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}