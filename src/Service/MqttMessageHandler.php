<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class MqttMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleMessage(string $topic, string $payload): void
    {
        $this->logger->info('MQTT message received', [
            'topic' => $topic,
            'payload' => $payload,
        ]);

        $data = [
            'topic' => $topic,
            'payload' => $this->decodePayload($payload),
            'received_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];

        dump($data);
        $this->logger->info(json_encode($data['payload']));

        return;
    }

    private function decodePayload(string $payload): mixed
    {
        $trimmed = trim($payload);
        if ($trimmed === '') {
            return '';
        }
        try {
            return json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $payload; // not JSON
        }
    }
}

