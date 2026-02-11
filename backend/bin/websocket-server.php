<?php

require dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel('prod', false);
$kernel->boot();

$container = $kernel->getContainer();

$server = $container->get(App\WebSocket\Server::class);
$server->run();