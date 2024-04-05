<?php

namespace App\controllers;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * The AbstractController class is an abstract class that provides common functionality
 * for controllers in a PHP application.
 */
abstract class AbstractController
{
    private ?ContainerInterface $container;
    private ?EntityManagerInterface $entityManager;


    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function getEntityManager(): ?EntityManagerInterface
    {
        return $this->entityManager;
    }




    public function __construct(
        ?ContainerInterface     $container,
        ?EntityManagerInterface $entityManager,
    ) {
        $this->container = $container;
        $this->entityManager = $entityManager;
    }

    /**
     * Generates a JSON response with the provided payload and status code.
     *
     * @param ResponseInterface $response The response object.
     * @param string $payload The JSON payload to be written to the response body.
     * @param int $status The HTTP status code (default: 200).
     * @return ResponseInterface modified response object.
     */
    protected function JSONResponse(ResponseInterface $response, string $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}