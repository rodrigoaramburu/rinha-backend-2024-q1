<?php

declare(strict_types=1);

namespace Rinha\Actions;

use Rinha\Exception\HttpException;
use Rinha\ConnectionPool;

class ExtratoAction
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {
    }

    public function handle(
        string $rawContent,
        array $vars
    ): string {
        $pdo = $this->connectionPool->getConnection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id, nome, limite, saldo FROM clientes WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $vars['id']]);

        $clientData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (empty($clientData)) {
            $pdo->rollBack();
            throw new HttpException(404, 'Cliente não encontrado');
        }

        $pstm = $pdo->prepare('SELECT * FROM transacoes WHERE cliente_id = :id  ORDER BY id DESC  LIMIT 10');
        $pstm->execute(['id' => $clientData['id']]);
        $dataTransacoes = $pstm->fetchAll(\PDO::FETCH_ASSOC);

        $pdo->commit();
        $pdo->detach();

        $data = json_encode([
            'saldo' => [
                'total' => $clientData['saldo'],
                'data_extrato' => (new \Datetime())->format('Y-m-d H:i:s'),
                'limite' => $clientData['limite'],
            ],
            'ultimas_transacoes' => $dataTransacoes,
        ]);

        return $data;
    }
}
