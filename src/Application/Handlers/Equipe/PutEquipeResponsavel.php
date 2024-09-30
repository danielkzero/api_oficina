<?php
namespace App\Application\Handlers\Equipe;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutEquipeResponsavel
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
        $idNovoResponsavel = (int)$data['usuario_id'];

        // Verifica se a equipe existe
        $stmt = $this->pdo->prepare("SELECT id FROM usuario_equipe WHERE id = :id AND excluido = 0");
        $stmt->bindParam(':id', $idEquipe);
        $stmt->execute();
        $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$equipe) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Equipe não encontrada']);
        }

        // Verifica se o usuário novo existe
        $stmt = $this->pdo->prepare("SELECT id FROM usuario WHERE id = :id AND excluido = 0");
        $stmt->bindParam(':id', $idNovoResponsavel);
        $stmt->execute();
        $novoResponsavel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$novoResponsavel) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Novo responsável não encontrado']);
        }

        // Verifica se já existe um responsável atual
        $stmt = $this->pdo->prepare("
            SELECT id, id_usuario FROM usuario_equipe_usuario 
            WHERE id_equipe = :id_equipe AND responsavel = 1 AND excluido = 0
        ");
        $stmt->bindParam(':id_equipe', $idEquipe);
        $stmt->execute();
        $responsavelAtual = $stmt->fetch(PDO::FETCH_ASSOC);

        // Inicia uma transação para garantir que tudo seja atualizado corretamente
        $this->pdo->beginTransaction();

        try {
            // Se já houver um responsável, o substitui (remove o responsável anterior)
            if ($responsavelAtual) {
                $stmtUpdate = $this->pdo->prepare("
                    UPDATE usuario_equipe_usuario 
                    SET responsavel = 0, ultima_alteracao = current_timestamp() 
                    WHERE id = :id
                ");
                $stmtUpdate->bindParam(':id', $responsavelAtual['id']);
                $stmtUpdate->execute();
            }

            // Verifica se o novo responsável já faz parte da equipe
            $stmtCheckNovoResponsavel = $this->pdo->prepare("
                SELECT id FROM usuario_equipe_usuario 
                WHERE id_usuario = :id_usuario AND id_equipe = :id_equipe AND excluido = 0
            ");
            $stmtCheckNovoResponsavel->bindParam(':id_usuario', $idNovoResponsavel);
            $stmtCheckNovoResponsavel->bindParam(':id_equipe', $idEquipe);
            $stmtCheckNovoResponsavel->execute();
            $novoResponsavelEquipe = $stmtCheckNovoResponsavel->fetch(PDO::FETCH_ASSOC);

            if ($novoResponsavelEquipe) {
                // Se o novo responsável já estiver na equipe, atualiza para ser o responsável
                $stmtUpdateNovo = $this->pdo->prepare("
                    UPDATE usuario_equipe_usuario 
                    SET responsavel = 1, ultima_alteracao = current_timestamp() 
                    WHERE id = :id
                ");
                $stmtUpdateNovo->bindParam(':id', $novoResponsavelEquipe['id']);
                $stmtUpdateNovo->execute();
            } else {
                // Se o novo responsável não estiver na equipe, o adiciona como responsável
                $stmtInsertNovo = $this->pdo->prepare("
                    INSERT INTO usuario_equipe_usuario (id_equipe, id_usuario, responsavel, cadastrado_em, ultima_alteracao, excluido)
                    VALUES (:id_equipe, :id_usuario, 1, current_timestamp(), current_timestamp(), 0)
                ");
                $stmtInsertNovo->bindParam(':id_equipe', $idEquipe);
                $stmtInsertNovo->bindParam(':id_usuario', $idNovoResponsavel);
                $stmtInsertNovo->execute();
            }

            // Commit na transação
            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Responsável atualizado com sucesso']);
        } catch (\Exception $e) {
            // Se houver erro, faz rollback
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao atualizar responsável', 'error' => $e->getMessage()]);
        }
    }
}
