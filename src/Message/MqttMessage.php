<?php

namespace App\Message;

final class MqttMessage
{
    public function __construct(
        private readonly string $topic,
        private readonly string $payload,
    ) {}

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}

