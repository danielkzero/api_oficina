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
            $nome = $body['nome'];
            $email = $body['email'];
            $usuario = $body['usuario'];
            $senha = md5($body['senha']);
            
            $stmt = $this->pdo->prepare('INSERT INTO usuario (nome, email, usuario, senha) VALUES (:nome, :email, :usuario, :senha)');
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':senha', $senha);
            $stmt->execute();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true]);

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}