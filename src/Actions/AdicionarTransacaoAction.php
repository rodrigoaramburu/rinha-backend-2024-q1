<?php

declare(strict_types=1);

namespace Rinha\Actions;

use Rinha\Exception\HttpException;
use Rinha\ConnectionPool;

class AdicionarTransacaoAction
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {

    }

    public function handle(
        string $rawContent,
        array $vars
    ): string {

        $requestData = json_decode($rawContent, true);
        if (! $this->validate($requestData)) {
            throw new HttpException(422, 'Valor inválido');
        }

        $pdo = $this->connectionPool->getConnection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT id, nome, limite, saldo FROM clientes WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $vars['id']]);

        $clientData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (empty($clientData)) {
            $pdo->rollBack();
            throw new HttpException(404, 'Cliente não encontrado');
        }

        $novoSaldo = $requestData['tipo'] == 'c'
            ? $clientData['saldo'] + $requestData['valor']
            : $clientData['saldo'] - $requestData['valor'];

        if ($requestData['tipo'] === 'd' && $novoSaldo < -$clientData['limite']) {
            $pdo->rollBack();
            throw new HttpException(422, 'Limite ultrapassado');
        }

        $stmt = $pdo->prepare('INSERT INTO transacoes (cliente_id, valor, tipo, descricao, realizada_em) VALUES (:cliente_id, :valor, :tipo, :descricao, :realizada_em)');
        $stmt->execute([
            'cliente_id' => $clientData['id'],
            'valor' => $requestData['valor'],
            'tipo' => $requestData['tipo'],
            'descricao' => $requestData['descricao'],
            'realizada_em' => (new \Datetime)->format('Y-m-d H:i:s'),
        ]);

        $stmt = $pdo->prepare('UPDATE clientes SET saldo = :novoSaldo WHERE id = :id');
        $stmt->execute([
            'novoSaldo' => $novoSaldo,
            'id' => $clientData['id'],
        ]);

        $pdo->commit();
        $pdo->detach();

        return json_encode([
            'id' => $clientData['id'],
            'nome' => $clientData['nome'],
            'limite' => $clientData['limite'],
            'saldo' => $novoSaldo,
        ]);
    }

    private function validate($data): bool
    {
        if (! isset($data['valor']) || ! is_int($data['valor']) || $data['valor'] <= 0) {
            return false;
        }

        if (! isset($data['tipo']) || $data['tipo'] != 'c' && $data['tipo'] != 'd') {
            return false;
        }

        if (
            ! isset($data['descricao']) ||
            $data['descricao'] === '' ||
            strlen($data['descricao']) < 0 ||
            strlen($data['descricao']) > 10) {
            return false;
        }

        return true;
    }
}
