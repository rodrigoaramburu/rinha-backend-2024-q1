<?php 

declare(strict_types=1);

use Rinha\Application;

include('vendor/autoload.php');


$app = new Swoole\Http\Server('0.0.0.0', 8000);

$app->on('start', function($server){
    echo 'Executando o Swoole';
});

$app->on('request', function($request, $response){
    $response->header('Content-Type', 'application/json');
    
    Application::getInstance()->dispatch($request, $response);

});

$app->start();