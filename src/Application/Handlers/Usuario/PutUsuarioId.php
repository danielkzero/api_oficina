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
                usuario=:usuario, 
                senha=:senha, 
                email=:email, 
                nome=:nome, 
                data_nascimento=:data_nascimento, 
                sexo=:sexo, 
                telefone=:telefone
            WHERE id=:id');            
            $stmt->bindParam(':usuario', $body['usuario']);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':email', $body['email']);
            $stmt->bindParam(':nome', $body['nome']);
            $stmt->bindParam(':data_nascimento', $body['data_nascimento']);
            $stmt->bindParam(':sexo', $body['sexo']);
            $stmt->bindParam(':telefone', $body['telefone']);
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