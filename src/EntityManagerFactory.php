<?php

namespace App;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class EntityManagerFactory
{
    /**
     * Creates a new instance of the EntityManager.
     *
     * @return EntityManager The created EntityManager instance.
     *
     * @throws Exception
     */
    public static function create(): EntityManager
    {
        $client = RedisAdapter::createConnection(
            'redis://redis'
        );
        $cache = new RedisAdapter($client);

        // Proxy directory
        $proxyDir = __DIR__ . '/../cache/proxies';

        $config = ORMSetup::createAttributeMetadataConfiguration(
            array(__DIR__.'/../src',),
            true,
            $proxyDir,
            $cache
        );

        $dsnParser = new DsnParser();
        $connectionParams = $dsnParser
            ->parse('mysqli://user:password@mysql:3306/app');

        $conn = DriverManager::getConnection($connectionParams);

        $eventManager = new EventManager();

        return new EntityManager($conn, $config, $eventManager);
    }
}