<?php

use App\EntityManagerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$definitions = [
	EntityManagerInterface::class => DI\factory([EntityManagerFactory::class, 'create']),
	StreamHandler::class          => DI\create()->constructor(__DIR__ . "/../var/log/app.log", Logger::ERROR),
	Logger::class                 => DI\create()->constructor('app.log', [DI\get(StreamHandler::class)]),
];

return $definitions;