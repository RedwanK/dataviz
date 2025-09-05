#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Kernel;
use App\Mqtt\MqttConsumer;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables from .env (Symfony Runtime is not used here)
if (class_exists(Dotenv::class)) {
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/.env');
}

$bootstrap = dirname(__DIR__) . '/config/bootstrap.php';
if (is_file($bootstrap)) {
    require $bootstrap;
}

$env = $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'dev';
$debug = (bool) (($_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG')) ?: ($env !== 'prod'));

$kernel = new Kernel($env, $debug);
$kernel->boot();

/** @var MqttConsumer $consumer */
$consumer = $kernel->getContainer()->get(MqttConsumer::class);

$host = getenv('MQTT_HOST') ?: 'mqtt';
$port = (int) (getenv('MQTT_PORT') ?: '1883');
$username = getenv('MQTT_USERNAME') ?: null;
$password = getenv('MQTT_PASSWORD') ?: null;
$topicsEnv = getenv('MQTT_TOPICS') ?: 'gateways/+';
$useTls = filter_var(getenv('MQTT_TLS') ?: '0', FILTER_VALIDATE_BOOL);
$clientId = getenv('MQTT_CLIENT_ID') ?: null;
$qos = (int) (getenv('MQTT_QOS') ?: '0');

// Split topics by comma or whitespace
$topicsEnv = str_replace(',', ' ', $topicsEnv);
$topics = array_values(array_filter(array_map('trim', preg_split('/\s+/', $topicsEnv) ?: []), fn(string $t) => $t !== ''));

$consumer->run($host, $port, $topics, $username, $password, $useTls, $clientId, $qos);

$kernel->shutdown();
exit(0);
