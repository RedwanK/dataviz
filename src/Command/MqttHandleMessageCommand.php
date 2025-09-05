<?php

namespace App\Command;

use App\Message\MqttMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:mqtt:handle',
    description: 'Handle a single MQTT message (topic, payload) and publish to Mercure.'
)]
class MqttHandleMessageCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $bus)
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

        // Dispatch to async transport via Messenger
        $this->bus->dispatch(new MqttMessage($topic, $payload));
        $output->writeln(sprintf('<info>Dispatched</info> %s', $topic));

        return Command::SUCCESS;
    }
}
