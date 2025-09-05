<?php

namespace App\Service;

use App\Entity\Device;
use App\Entity\Tag;
use App\Repository\DeviceRepository;
use App\Repository\GatewayRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MqttMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GatewayRepository $gatewayRepository,
        private readonly DeviceRepository $deviceRepository,
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    
    public function handleMessage(string $topic, string $payload): void
    {
        $this->logger->info('MQTT message received', [
            'topic' => $topic,
        ]);

        // Parse topic: gateways/<gateway_id>/<device_id>
        $parts = explode('/', trim($topic, '/'));
        if (count($parts) < 3 || $parts[0] !== 'gateways') {
            $this->logger->error('Unsupported topic format', ['topic' => $topic]);
            return;
        }
        [$prefix, $gatewayId, $deviceId] = [$parts[0], $parts[1], $parts[2]];

        // 1) Gateway lookup by name (assumes name == gateway_id)
        $gateway = $this->gatewayRepository->findOneBy(['id' => $gatewayId]);
        if (!$gateway) {
            $this->logger->error('Gateway not found; skipping message', [
                'gateway_id' => $gatewayId,
                'device_id' => $deviceId,
            ]);
            return;
        }

        // 2) Device lookup by code within gateway, create if missing
        $device = $this->deviceRepository->findOneBy(['gateway' => $gateway, 'code' => $deviceId]);
        if (!$device) {
            $device = new Device();
            $device->setGateway($gateway);
            $device->setCode($deviceId);
            $this->em->persist($device);
            $this->em->flush();
            $this->logger->info('Device created and linked to gateway', [
                'gateway_id' => $gatewayId,
                'device_id' => $deviceId,
            ]);
        }

        $decoded = $this->decodePayload($payload);
        $data = [
            'topic' => $topic,
            'payload' => $decoded,
            'received_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];

        // 3) Update tags based on payload
        try {
            $this->upsertTagsFromPayload($device, $decoded);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to upsert tags', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $data = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        return;
    }

    private function decodePayload(string $payload): mixed
    {
        // Strip UTF-8 BOM if present and trim whitespace
        $payload = preg_replace('/^\xEF\xBB\xBF/', '', $payload ?? '');
        $trimmed = trim($payload);
        if ($trimmed === '') {
            return '';
        }
        try {
            return json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // Try lenient sanitize: remove trailing commas before } or ]
            $sanitized = $this->sanitizeJson($trimmed);
            if ($sanitized !== $trimmed) {
                try {
                    return json_decode($sanitized, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e2) {
                    $this->logger->warning('Payload JSON invalid after sanitize; using raw string', [
                        'error' => $e2->getMessage(),
                    ]);
                    return $payload;
                }
            }
            $this->logger->warning('Payload is not valid JSON, using raw string', [
                'error' => $e->getMessage(),
            ]);
            return $payload; // not JSON
        }
    }

    private function sanitizeJson(string $json): string
    {
        $before = $json;
        do {
            $prev = $json;
            $json = preg_replace('/,\s*([}\]])/m', '$1', $json);
        } while ($json !== null && $json !== $prev);
        return $json ?? $before;
    }

    /**
     *   Expected JSON payload format (example):
     *   {
     *     "d": [
     *       { "tag": "AI.0", "value": 12.00 },
     *       { "tag": "AI.1", "value": 12.00 }
     *     ],
     *     "ts": "2017-12-22T08:05:20+0000"
     *   }
     *   Notes:
     *   - Each entry in "d" must contain a string "tag" and a "value" of any JSON type.
     *   - Tag entity is created if missing with Tag.code = tag and Tag.dataType inferred from value:
     *     integer→"integer", float→"double precision", boolean→"boolean", string→"text", array/object→"jsonb", null→"text".
     *   - Existing tags will have their dataType updated if the inferred type changes.
     */
    private function upsertTagsFromPayload(Device $device, mixed $payload): void
    {
        if (!is_array($payload)) {
            return; // not JSON -> nothing to map
        }
        $dataArray = $payload['d'] ?? null;
        if (!is_array($dataArray)) {
            return;
        }

        foreach ($dataArray as $item) {
            if (!is_array($item) || !isset($item['tag']) || !array_key_exists('value', $item)) {
                continue;
            }
            $code = (string) $item['tag'];
            $value = $item['value'];
            $dataType = $this->mapPhpValueToPostgresType($value);

            $tag = $this->tagRepository->findOneBy(['device' => $device, 'code' => $code]);
            if (!$tag) {
                $tag = new Tag();
                $tag->setDevice($device);
                $tag->setCode($code);
                $tag->setDataType($dataType);
                $this->em->persist($tag);
            } else {
                // Optionally update datatype if changed
                if ($tag->getDataType() !== $dataType) {
                    $tag->setDataType($dataType);
                }
            }
        }

        $this->em->flush();
    }

    private function mapPhpValueToPostgresType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'integer',
            is_float($value) => 'double precision',
            is_bool($value) => 'boolean',
            is_string($value) => 'text',
            is_array($value) => 'jsonb',
            is_object($value) => 'jsonb',
            $value === null => 'text',
            default => 'text',
        };
    }
}
