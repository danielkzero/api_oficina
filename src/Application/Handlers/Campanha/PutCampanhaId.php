<?php
//routes.php
namespace App\Application\Handlers\Campanha;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutCampanhaId
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            $id = $args['id'];

            $qtd_desconto=($body['qtd_desconto'] != null ? json_encode($body['qtd_desconto']) : null);
            $valor_desconto=($body['valor_desconto'] != null ? json_encode($body['valor_desconto']) : null);

            $cotas_premiadas_descricao=($body['cotas_premiadas_descricao'] != null ? json_encode($body['cotas_premiadas_descricao']) : null);

            $cotas_premiadas=($body['cotas_premiadas'] != null ? json_encode($body['cotas_premiadas']) : null);
            $descricao_lista_premiacao=($body['descricao_lista_premiacao'] != null ? json_encode($body['descricao_lista_premiacao']) : null);

            $subtitulo = ($body['subtitulo'] != null ? str_replace('color: rgb(0, 0, 0);','',$body['subtitulo']) : null);
            
            $slug = $this->convertToTag($body['nome']);

            $stmt = $this->pdo->prepare('UPDATE campanhas SET
                nome=:nome,
                descricao=:descricao,
                preco=:preco,
                imagem_principal=:imagem_principal,
                status=:status,
                excluido=:excluido,

                qtd_numeros=:qtd_numeros,
                min_compra=:min_compra,
                max_compra=:max_compra,

                slug=CONCAT( :slug , "_", codigo),

                numeros_pendentes=:numeros_pendentes,
                numeros_pagos=:numeros_pagos,

                galeria_imagens=:galeria_imagens,
                ativar_barra_progresso=:ativar_barra_progresso,
                numero_do_sorteio=:numero_do_sorteio,
                status_display=:status_display,
                subtitulo=:subtitulo,

                qtd_desconto=:qtd_desconto,
                valor_desconto=:valor_desconto,

                ativar_ranking=:ativar_ranking,

                vencedor_sorteio=:vencedor_sorteio,

                sorteio_privado=:sorteio_privado,
                sorteio_destaque=:sorteio_destaque,

                cotas_premiadas=:cotas_premiadas,
                cotas_premiadas_descricao=:cotas_premiadas_descricao,

                telefone_suporte=:telefone_suporte,

                ativar_data_sorteio=:ativar_data_sorteio,
                data_sorteio=:data_sorteio,

                tempo_pagamento=:tempo_pagamento,
                requisitar_email=:requisitar_email,
                descricao_lista_premiacao=:descricao_lista_premiacao,

                encerrada=:encerrada
            WHERE id=:id');
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nome', $body['nome']);
            $stmt->bindParam(':descricao', $subtitulo);
            $stmt->bindParam(':preco', $body['preco']);
            $stmt->bindParam(':imagem_principal', $body['imagem_principal']);
            $stmt->bindParam(':status', $body['status']);
            $stmt->bindParam(':excluido', $body['excluido']);
            $stmt->bindParam(':imagem_principal', $body['imagem_principal']);

            $stmt->bindParam(':qtd_numeros', $body['qtd_numeros']);
            $stmt->bindParam(':min_compra', $body['min_compra']);
            $stmt->bindParam(':max_compra', $body['max_compra']);

            $stmt->bindParam(':slug', $slug);

            $stmt->bindParam(':numeros_pendentes', $body['numeros_pendentes']);
            $stmt->bindParam(':numeros_pagos', $body['numeros_pagos']);

            $stmt->bindParam(':galeria_imagens', $body['galeria_imagens']);
            $stmt->bindParam(':ativar_barra_progresso', $body['ativar_barra_progresso']);
            $stmt->bindParam(':numero_do_sorteio', $body['numero_do_sorteio']);
            $stmt->bindParam(':status_display', $body['status_display']);
            $stmt->bindParam(':subtitulo', $subtitulo);

            $stmt->bindParam(':qtd_desconto', $qtd_desconto);
            $stmt->bindParam(':valor_desconto', $valor_desconto);

            $stmt->bindParam(':ativar_ranking', $body['ativar_ranking']);

            $stmt->bindParam(':vencedor_sorteio', $body['vencedor_sorteio']);

            $stmt->bindParam(':sorteio_privado', $body['sorteio_privado']);
            $stmt->bindParam(':sorteio_destaque', $body['sorteio_destaque']);

            $stmt->bindParam(':cotas_premiadas', $cotas_premiadas);
            $stmt->bindParam(':cotas_premiadas_descricao', $cotas_premiadas_descricao);

            $stmt->bindParam(':telefone_suporte', $body['telefone_suporte']);

            $stmt->bindParam(':ativar_data_sorteio', $body['ativar_data_sorteio']);
            $stmt->bindParam(':data_sorteio', $body['data_sorteio']);

            $stmt->bindParam(':tempo_pagamento', $body['tempo_pagamento']);
            $stmt->bindParam(':requisitar_email', $body['requisitar_email']);
            $stmt->bindParam(':descricao_lista_premiacao', $descricao_lista_premiacao);
            
            $stmt->bindParam(':encerrada', $body['encerrada']);
            $stmt->bindParam(':publicado', $body['publicado']);
            $stmt->execute();


            return $response->withHeader('Content-Type', 'application/json')->withJson(
                [
                    'success' => true, 
                ]
            );

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }

    public function convertToTag($string) {
        // Converte todos os caracteres para minúsculas
        $string = strtolower($string);
    
        // Remove acentos e caracteres especiais
        $unwantedArray = array(
            'á'=>'a', 'à'=>'a', 'ã'=>'a', 'â'=>'a', 'ä'=>'a',
            'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
            'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
            'ó'=>'o', 'ò'=>'o', 'õ'=>'o', 'ô'=>'o', 'ö'=>'o',
            'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u',
            'ç'=>'c', 'ñ'=>'n'
        );
        $string = strtr($string, $unwantedArray);
    
        // Substitui espaços e caracteres não alfanuméricos por underscores (_)
        $string = preg_replace('/[^a-z0-9]/', '_', $string);
    
        // Remove underscores consecutivos
        $string = preg_replace('/_+/', '_', $string);
    
        // Remove underscores no início e no fim da string
        $string = trim($string, '_');
    
        return $string;
    }
}