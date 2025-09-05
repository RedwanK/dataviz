<?php

namespace App\MessageHandler;

use App\Message\TagReadingsBatchMessage;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PersistTagReadingsHandler
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(TagReadingsBatchMessage $msg): void
    {
        $ts = $msg->getTimestamp()->format('Y-m-d H:i:sP');
        $rows = $msg->getReadings();
        if ($rows === []) {
            return;
        }

        $this->connection->beginTransaction();
        try {
            $sql = 'INSERT INTO tag_reading ("time", device_id, tag_id, gateway_id, value_type, value_num, value_bool, value_text, value_json)
                    VALUES (:time, :device_id, :tag_id, :gateway_id, :value_type, :value_num, :value_bool, :value_text, :value_json)';
            $stmt = $this->connection->prepare($sql);
            foreach ($rows as $r) {
                $stmt->bindValue('time', $ts);
                $stmt->bindValue('device_id', $r['device_id']);
                $stmt->bindValue('tag_id', $r['tag_id']);
                $stmt->bindValue('gateway_id', $r['gateway_id']);
                $stmt->bindValue('value_type', $r['value_type']);
                $stmt->bindValue('value_num', $r['value_num']);
                $stmt->bindValue('value_bool', $r['value_bool']);
                $stmt->bindValue('value_text', $r['value_text']);
                $stmt->bindValue('value_json', is_array($r['value_json']) ? json_encode($r['value_json']) : $r['value_json']);
                $stmt->executeStatement();
            }
            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $this->logger->error('Failed to persist tag readings', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

