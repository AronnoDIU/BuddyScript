<?php

declare(strict_types=1);

namespace ApiBundle\EventListener;

use ApiBundle\Exception\ValidationException;
use CoreBundle\Exception\RateLimitException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ValidationException) {
            $event->setResponse(new JsonResponse([
                'message' => 'Validation failed.',
                'errors' => $exception->getErrors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        if ($exception instanceof RateLimitException) {
            $event->setResponse(new JsonResponse([
                'message' => 'Rate limit exceeded. Please try again later.',
            ], Response::HTTP_TOO_MANY_REQUESTS));

            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $event->setResponse(new JsonResponse([
                'message' => 'Resource not found.',
            ], Response::HTTP_NOT_FOUND));

            return;
        }

        if ($exception instanceof BadRequestHttpException) {
            $event->setResponse(new JsonResponse([
                'message' => 'The request could not be processed.',
            ], Response::HTTP_BAD_REQUEST));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            if ($statusCode === Response::HTTP_FORBIDDEN) {
                $message = 'Access denied.';
            } elseif ($statusCode === Response::HTTP_UNAUTHORIZED) {
                $message = 'Unauthorized.';
            } else {
                $message = 'An error occurred.';
            }

            $event->setResponse(new JsonResponse([
                'message' => $message,
            ], $statusCode));
        }
    }
}
