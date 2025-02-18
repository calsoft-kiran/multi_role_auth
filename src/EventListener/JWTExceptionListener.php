<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class JWTExceptionListener
{
    #[AsEventListener(event: JWTExpiredEvent::class)]
    public function onJWTExpired(JWTExpiredEvent $event): void
    {
        $response = new JsonResponse([
            'data'=>[],
            'error' => 401,
            'message' => 'Your session has expired. Please log in again.'
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }

    #[AsEventListener(event: JWTNotFoundEvent::class)]
    public function onJWTNotFound(JWTNotFoundEvent $event): void
    {
        $response = new JsonResponse([
            'data'=>[],
            'error' => 401,
            'message' => 'JWT Token not found. Please provide a valid token.'
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
