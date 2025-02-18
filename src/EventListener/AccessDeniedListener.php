<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'kernel.exception', priority: 10)]
class AccessDeniedListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            $response = new JsonResponse([
                'data'=>[],
                'error' => 'Access Denied',
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_FORBIDDEN);

            $event->setResponse($response);
        }
    }
}
