<?php
namespace App\Application\Handlers\Equipe;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PostEquipe
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $stmt = $this->pdo->prepare("
            INSERT INTO usuario_equipe (nome, cadastrado_em)
            VALUES (:nome, current_timestamp())
        ");
        $stmt->bindParam(':nome', $data['nome']);
        $stmt->execute();
        
        $equipeId = $this->pdo->lastInsertId();

        // Inserir os usuários na equipe
        foreach ($data['usuarios'] as $usuario) {
            $stmtUsuario = $this->pdo->prepare("
                INSERT INTO usuario_equipe_usuario (id_equipe, id_usuario, responsavel, cadastrado_em)
                VALUES (:id_equipe, :id_usuario, :responsavel, current_timestamp())
            ");
            $stmtUsuario->bindParam(':id_equipe', $equipeId);
            $stmtUsuario->bindParam(':id_usuario', $usuario['id_usuario']);
            $stmtUsuario->bindParam(':responsavel', $usuario['responsavel']);
            $stmtUsuario->execute();
        }

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Equipe criada com sucesso', 'id' => $equipeId]);
    }
}
