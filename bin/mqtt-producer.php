#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Kernel;
use App\Service\BusProvider;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables from .env (Symfony Runtime is not used here)
if (class_exists(Dotenv::class)) {
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/.env');
}

// Load env (similar to bin/console bootstrap)
$bootstrap = dirname(__DIR__) . '/config/bootstrap.php';
if (is_file($bootstrap)) {
    require $bootstrap;
}

$env = $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'dev';
$debug = (bool) (($_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG')) ?: ($env !== 'prod'));

$kernel = new Kernel($env, $debug);
$kernel->boot();

/** @var BusProvider $provider */
$provider = $kernel->getContainer()->get(BusProvider::class);
$bus = $provider->bus();

$stdin = fopen('php://stdin', 'r');
if ($stdin === false) {
    fwrite(STDERR, "[mqtt-producer] Unable to open STDIN\n");
    exit(1);
}

fwrite(STDOUT, "[mqtt-producer] Ready. Reading JSON lines from STDIN...\n");

while (!feof($stdin)) {
    $line = fgets($stdin);
    if ($line === false) {
        usleep(50000);
        continue;
    }
    $line = rtrim($line, "\r\n");
    if ($line === '') {
        continue;
    }

    // Expect JSON line: {"t":"<topic>","p":<json-escaped string>}
    $obj = json_decode($line, true);
    if (!is_array($obj) || !isset($obj['t']) || !array_key_exists('p', $obj)) {
        fwrite(STDERR, "[mqtt-producer] Malformed input line (not JSON)\n");
        continue;
    }
    $topic = (string) $obj['t'];
    $payload = is_string($obj['p']) ? $obj['p'] : json_encode($obj['p']);

    try {
        $bus->dispatch(new \App\Message\MqttMessage($topic, $payload));
    } catch (\Throwable $e) {
        fwrite(STDERR, sprintf("[mqtt-producer] Dispatch error: %s\n", $e->getMessage()));
    }
}

$kernel->shutdown();
fclose($stdin);
exit(0);
