<?php

namespace App\Mqtt;

use App\Message\MqttMessage;
use App\Service\BusProvider;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Psr\Log\LoggerInterface;

class MqttConsumer
{
    public function __construct(
        private readonly BusProvider $busProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function run(
        string $host,
        int $port,
        array $topics,
        ?string $username = null,
        ?string $password = null,
        bool $useTls = false,
        ?string $clientId = null,
        int $qos = 0,
        int $keepAlive = 60
    ): void {
        $clientId = $clientId ?: ('symfony-mqtt-bridge-' . bin2hex(random_bytes(4)));
        $this->logger->info('Starting native MQTT consumer', [
            'host' => $host,
            'port' => $port,
            'topics' => $topics,
            'tls' => $useTls,
            'client_id' => $clientId,
            'qos' => $qos,
        ]);

        $settings = (new ConnectionSettings())
            ->setUseTls($useTls)
            ->setTlsSelfSignedAllowed(true)
            ->setKeepAliveInterval($keepAlive);

        if ($username !== null && $username !== '' && $password !== null && $password !== '') {
            $settings = $settings->setUsername($username)->setPassword($password);
        }

        while (true) {
            $client = new MqttClient($host, $port, $clientId);
            try {
                $client->connect($settings, true);
                foreach ($topics as $t) {
                    $topic = trim((string) $t);
                    if ($topic === '') {
                        continue;
                    }
                    $client->subscribe($topic, function (string $topic, string $message, bool $retained) use ($qos) {
                        try {
                            $this->busProvider->bus()->dispatch(new MqttMessage($topic, $message));
                        } catch (\Throwable $e) {
                            $this->logger->error('Dispatch error', [
                                'error' => $e->getMessage(),
                                'topic' => $topic,
                            ]);
                        }
                    }, $qos);
                }
                $this->logger->info('Connected to MQTT broker, entering loop');
                // Loop indefinitely; the client handles keepalive pings
                $client->loop(true);
            } catch (\Throwable $e) {
                $this->logger->error('MQTT consumer error, will retry', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Ensure disconnect before retrying
                try { $client->disconnect(); } catch (\Throwable) {}
                sleep(3);
            }
        }
    }
}

