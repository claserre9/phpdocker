<?php

namespace App;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
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

        $queryCache = new RedisAdapter($client);
        $metadataCache = new RedisAdapter($client);

        $config = new Configuration;
        $config->setMetadataCache($metadataCache);
        $driverImpl = new AttributeDriver([__DIR__.'/../src/entities'], true);
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCache($queryCache);
        $config->setProxyDir(__DIR__ . '/../cache/proxies');
        $config->setProxyNamespace('App\proxies');
        $config->setAutoGenerateProxyClasses(false);

        $dsnParser = new DsnParser();
        $connectionParams = $dsnParser
            ->parse('mysqli://user:password@mysql:3306/app');

        $connection = DriverManager::getConnection($connectionParams);

        $eventManager = new EventManager();

        return new EntityManager($connection, $config, $eventManager);
    }
}