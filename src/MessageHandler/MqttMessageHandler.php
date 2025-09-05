<?php

namespace App\MessageHandler;

use App\Message\MqttMessage;
use App\Service\MqttMessageHandler as ServiceHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class MqttMessageHandler
{
    public function __construct(private readonly ServiceHandler $handler)
    {
    }

    public function __invoke(MqttMessage $message): void
    {
        $this->handler->handleMessage($message->getTopic(), $message->getPayload());
    }
}

