<?php

namespace App\Service;

use Symfony\Component\Messenger\MessageBusInterface;

class BusProvider
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
    }

    public function bus(): MessageBusInterface
    {
        return $this->bus;
    }
}

