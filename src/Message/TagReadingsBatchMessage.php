<?php

namespace App\Message;

final class TagReadingsBatchMessage
{
    /**
     * @param array<int, array{tag_id:int, device_id:int, gateway_id:int|null, value_type:string, value_num:float|null, value_bool:bool|null, value_text:?string, value_json:array|string|null}> $readings
     */
    public function __construct(
        private readonly \DateTimeImmutable $timestamp,
        private readonly array $readings,
    ) {}

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * @return array<int, array{tag_id:int, device_id:int, gateway_id:int|null, value_type:string, value_num:float|null, value_bool:bool|null, value_text:?string, value_json:array|string|null}>
     */
    public function getReadings(): array
    {
        return $this->readings;
    }
}

