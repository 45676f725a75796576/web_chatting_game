<?php
namespace App\Command;

use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebSocketServerCommand extends Command
{
    protected static $defaultName = 'app:ws-server';

    protected function configure(): void
    {
        $this->setDescription('Runs the WebSocket server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('starting websocket connection');

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new class implements MessageComponentInterface {
                        public function onOpen(ConnectionInterface $conn)
                        {
                            $conn->send("hello");
                        }
                        public function onMessage(ConnectionInterface $from, $msg) {}
                        public function onClose(ConnectionInterface $conn) {}
                        public function onError(ConnectionInterface $conn, \Exception $e) {
                            $conn->close();
                        }
                    }
                )
            ),
            8080
        );

        $server->run();

        return Command::SUCCESS;
    }
}
