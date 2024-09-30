<?php
namespace App\Application\Handlers\Equipe;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class DeleteEquipeMembro
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $idEquipe = (int)$args['id'];
        $data = $request->getParsedBody();
        $idUsuario = (int)$data['id_usuario'];

        // Verifica se a equipe existe
        $stmtEquipe = $this->pdo->prepare("SELECT id FROM usuario_equipe WHERE id = :id_equipe AND excluido = 0");
        $stmtEquipe->bindParam(':id_equipe', $idEquipe);
        $stmtEquipe->execute();
        $equipe = $stmtEquipe->fetch(PDO::FETCH_ASSOC);

        if (!$equipe) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Equipe não encontrada']);
        }

        // Verifica se o usuário faz parte da equipe
        $stmtUsuarioEquipe = $this->pdo->prepare("
            SELECT id 
            FROM usuario_equipe_usuario 
            WHERE id_equipe = :id_equipe AND id_usuario = :id_usuario AND excluido = 0
        ");
        $stmtUsuarioEquipe->bindParam(':id_equipe', $idEquipe);
        $stmtUsuarioEquipe->bindParam(':id_usuario', $idUsuario);
        $stmtUsuarioEquipe->execute();
        $usuarioEquipe = $stmtUsuarioEquipe->fetch(PDO::FETCH_ASSOC);

        if (!$usuarioEquipe) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Membro não encontrado na equipe']);
        }

        // Inicia uma transação para garantir que tudo seja atualizado corretamente
        $this->pdo->beginTransaction();

        try {
            // Marca o membro como excluído, ao invés de deletá-lo fisicamente
            $stmtDelete = $this->pdo->prepare("
                UPDATE usuario_equipe_usuario 
                SET excluido = 1, ultima_alteracao = current_timestamp() 
                WHERE id = :id
            ");
            $stmtDelete->bindParam(':id', $usuarioEquipe['id']);
            $stmtDelete->execute();

            // Commit na transação
            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Membro removido da equipe com sucesso']);
        } catch (\Exception $e) {
            // Se houver erro, faz rollback
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao remover o membro da equipe', 'error' => $e->getMessage()]);
        }
    }
}
