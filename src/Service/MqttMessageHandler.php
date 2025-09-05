<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MqttMessageHandler
{
    public function __construct(
        private readonly HubInterface $hub,
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

        try {
            $data = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        // Publish to Mercure: namespace topics under "mqtt/"
        $update = new Update(
            topics: [sprintf('mqtt/%s', $topic)],
            data: $data
        );

        $this->hub->publish($update);
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

