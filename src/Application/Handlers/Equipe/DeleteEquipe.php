<?php
namespace App\Application\Handlers\Equipe;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class DeleteEquipe
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        // Verifica se a equipe existe
        $stmt = $this->pdo->prepare("SELECT id FROM usuario_equipe WHERE id = :id AND excluido = 0");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$equipe) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Equipe não encontrada']);
        }

        // Excluir logicamente a equipe
        $stmtDeleteEquipe = $this->pdo->prepare("UPDATE usuario_equipe SET excluido = 1 WHERE id = :id");
        $stmtDeleteEquipe->bindParam(':id', $id);
        $stmtDeleteEquipe->execute();

        // Excluir logicamente os usuários associados à equipe
        $stmtDeleteUsuarios = $this->pdo->prepare("UPDATE usuario_equipe_usuario SET excluido = 1 WHERE id_equipe = :id_equipe");
        $stmtDeleteUsuarios->bindParam(':id_equipe', $id);
        $stmtDeleteUsuarios->execute();

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Equipe e seus usuários foram excluídos com sucesso']);
    }
}
