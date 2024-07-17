<?php
namespace App\Application\Handlers\Pagamento;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;
use MercadoPago;

class ChecarPagamento
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function getMercadoPagoAccessToken()
    {
        $stmt = $this->pdo->prepare("SELECT meta_value FROM informacoes_sistema WHERE meta_field = 'mercadopago_access_token'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['meta_value'];
        } else {
            throw new Exception("MercadoPago access token not found");
        }
    }

    private function getUsedNumbers($id_campanha)
    {
        $stmt = $this->pdo->prepare("SELECT numeros_pedido FROM pedidos WHERE id_campanha = :id_campanha");
        $stmt->execute(['id_campanha' => $id_campanha]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $usedNumbers = [];
        
        foreach ($result as $row) {
            $usedNumbers = array_merge($usedNumbers, explode(',', $row['numeros_pedido']));
        }
        return array_map('intval', $usedNumbers);
    }

    private function generateUniqueNumbers($quantity, $usedNumbers, $maxNumber = 1000)
    {
        $uniqueNumbers = [];
        while (count($uniqueNumbers) < $quantity) {
            $number = rand(1, $maxNumber);
            if (!in_array($number, $usedNumbers) && !in_array($number, $uniqueNumbers)) {
                $uniqueNumbers[] = $number;
            }
        }
        return $uniqueNumbers;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            // Configurar e usar a API do Mercado Pago
            $accessToken = $this->getMercadoPagoAccessToken();
            MercadoPago\SDK::setAccessToken($accessToken);

            // Buscar pagamentos pendentes no banco de dados
            $stmt = $this->pdo->query("SELECT 
            a.*,
            b.id_campanha,
            b.quantidade,
            b.preco,
            b.numeros_pedido,
            b.pedido_expira_em,
            c.qtd_numeros 
            FROM pagamentos AS a
            INNER JOIN pedidos AS b ON b.id=a.id_pedido 
            INNER JOIN campanhas AS c ON c.id=b.id_campanha WHERE a.status = 'pending'");
            $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $updatedPayments = [];

            foreach ($pagamentos as $pagamento) {
                $referencia = json_decode($pagamento['referencia_pagamento'], true);
                $mercadoPagoId = $referencia['mercado_pago_id'];

                // Verificar o status do pagamento no Mercado Pago
                $payment = MercadoPago\Payment::find_by_id($mercadoPagoId);
                if ($payment) {
                    $status = 'approved';
                    //$status = $payment->status;
                    
                    // Atualizar o status do pagamento no banco de dados
                    $stmt = $this->pdo->prepare("UPDATE pagamentos SET status = :status WHERE id = :id");
                    $stmt->execute(['status' => $status, 'id' => $pagamento['id']]);

                    if ($status == 'approved') {
                        // Obter a quantidade de números comprados e a campanha associada
                        $id_campanha = $pagamento['id_campanha']; // Assumindo que id_campanha está na referencia_pagamento
                        $quantity = $pagamento['quantidade']; // Assumindo que quantity está na referencia_pagamento
                        $id = $pagamento['id_pedido'];

                        // Buscar os números já usados para a campanha
                        $usedNumbers = $this->getUsedNumbers($id_campanha);

                        // Gerar números únicos que não estão na lista de números usados
                        $uniqueNumbers = $this->generateUniqueNumbers($quantity, $usedNumbers, $pagamento['qtd_numeros']);

                        // Inserir os números sorteados na tabela pedidos
                        $stmt = $this->pdo->prepare("UPDATE pedidos SET numeros_pedido=:numeros_pedido WHERE id=:id");
                        $stmt->execute([
                            'id' => $id,
                            'numeros_pedido' => implode(',', $uniqueNumbers),
                        ]);
                    }

                    $updatedPayments[] = ['id' => $pagamento['id'], 'status' => $status];
                }
            }

            return $response->withHeader('Content-Type', 'application/json')->withJson($updatedPayments);

        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
