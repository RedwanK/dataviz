<?php

namespace App\Command;

use App\Service\MqttMessageHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:mqtt:handle',
    description: 'Handle a single MQTT message (topic, payload) and publish to Mercure.'
)]
class MqttHandleMessageCommand extends Command
{
    public function __construct(private readonly MqttMessageHandler $handler)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('topic', InputArgument::REQUIRED, 'The MQTT topic')
            ->addArgument('payload', InputArgument::REQUIRED, 'The MQTT payload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $topic = (string) $input->getArgument('topic');
        $payload = (string) $input->getArgument('payload');

        $this->handler->handleMessage($topic, $payload);
        $output->writeln(sprintf('<info>Handled</info> %s', $topic));

        return Command::SUCCESS;
    }
}

