<?php

namespace App\Command;

use App\WebSocket\Server;
use React\EventLoop\Factory;
use React\Socket\SocketServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'websocket:server')]
class WebSocketServerCommand extends Command
{
    public function __construct(
        private Server $webSocketServer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loop = Factory::create();

        $socket = new SocketServer('0.0.0.0:8080', [], $loop);

        $server = new IoServer(
            new HttpServer(
                new WsServer($this->webSocketServer)
            ),
            $socket,
            $loop
        );

        $output->writeln('<info>WebSocket server running on ws://localhost:8080</info>');

        $loop->run();

        return Command::SUCCESS;
    }
}
