<?php

declare(strict_types=1);

namespace Rinha;

use Rinha\PdoPool;

class ConnectionPool
{
    private $conns = [];

    private $numConnection = 15;

    public function __construct()
    {
        $host = getenv('DB_HOST');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $database = getenv('DB_NAME');

        $dsn = 'pgsql:dbname='.$database.';host='.$host;

        try {
            for ($i = 0; $i < $this->numConnection; $i++) {
                $this->conns[] = new PdoPool($dsn, $user, $pass, ['charset' => 'utf8']);
            }
        } catch (\Throwable $t) {
            throw $t;
        }
    }

    public function getConnection(): PdoPool
    {

        foreach($this->conns as $conn) {
            if (!$conn->isBusy()) {
                $conn->attach();
                return $conn;
            }
        }

        return $this->conns[rand(0, $this->numConnection - 1)];
    }
}
