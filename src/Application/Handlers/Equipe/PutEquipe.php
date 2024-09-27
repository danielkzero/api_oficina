<?php
namespace App\Application\Handlers\Equipe;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class UpdateEquipe
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        // Verifica se a equipe existe
        $stmt = $this->pdo->prepare("SELECT id FROM usuario_equipe WHERE id = :id AND excluido = 0");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$equipe) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Equipe não encontrada']);
        }

        // Atualiza os dados da equipe
        $stmtUpdateEquipe = $this->pdo->prepare("
            UPDATE usuario_equipe 
            SET nome = :nome, atualizado_em = current_timestamp() 
            WHERE id = :id
        ");
        $stmtUpdateEquipe->bindParam(':nome', $data['nome']);
        $stmtUpdateEquipe->bindParam(':id', $id);
        $stmtUpdateEquipe->execute();

        // Atualiza os usuários associados à equipe
        if (isset($data['usuarios']) && is_array($data['usuarios'])) {
            foreach ($data['usuarios'] as $usuario) {
                if (isset($usuario['id'])) {
                    // Verifica se o usuário já existe na equipe
                    $stmtCheckUsuario = $this->pdo->prepare("
                        SELECT id 
                        FROM usuario_equipe_usuario 
                        WHERE id_usuario = :id_usuario AND id_equipe = :id_equipe AND excluido = 0
                    ");
                    $stmtCheckUsuario->bindParam(':id_usuario', $usuario['id']);
                    $stmtCheckUsuario->bindParam(':id_equipe', $id);
                    $stmtCheckUsuario->execute();
                    $usuarioEquipe = $stmtCheckUsuario->fetch(PDO::FETCH_ASSOC);

                    if ($usuarioEquipe) {
                        // Atualiza o usuário existente na equipe
                        $stmtUpdateUsuario = $this->pdo->prepare("
                            UPDATE usuario_equipe_usuario 
                            SET responsavel = :responsavel, atualizado_em = current_timestamp() 
                            WHERE id = :id
                        ");
                        $stmtUpdateUsuario->bindParam(':responsavel', $usuario['responsavel']);
                        $stmtUpdateUsuario->bindParam(':id', $usuarioEquipe['id']);
                        $stmtUpdateUsuario->execute();
                    } else {
                        // Adiciona um novo usuário à equipe
                        $stmtInsertUsuario = $this->pdo->prepare("
                            INSERT INTO usuario_equipe_usuario (id_equipe, id_usuario, responsavel, cadastrado_em, atualizado_em, excluido)
                            VALUES (:id_equipe, :id_usuario, :responsavel, current_timestamp(), current_timestamp(), 0)
                        ");
                        $stmtInsertUsuario->bindParam(':id_equipe', $id);
                        $stmtInsertUsuario->bindParam(':id_usuario', $usuario['id']);
                        $stmtInsertUsuario->bindParam(':responsavel', $usuario['responsavel']);
                        $stmtInsertUsuario->execute();
                    }
                }
            }
        }

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Equipe atualizada com sucesso']);
    }
}
