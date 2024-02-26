<?php

declare(strict_types=1);

use Rinha\Actions\AdicionarTransacaoAction;
use Rinha\Actions\ExtratoAction;

return function ($route) {

    $route->addRoute('POST', '/clientes/{id}/transacoes', AdicionarTransacaoAction::class);
    $route->addRoute('GET', '/clientes/{id}/extrato', ExtratoAction::class);

};
