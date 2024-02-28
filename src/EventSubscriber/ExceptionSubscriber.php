<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ApiValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // :TODO: This needs to be refactored

        // Get the exception object from the received event
        $exception = $event->getThrowable();

        if ($exception instanceof ApiValidationException) {
            $data = [
                'status' => 'error',
                'message' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ];

            // Customize your response object to display the exception details
            $response = new JsonResponse($data, $exception->getStatusCode());

            // sends the modified response object to the event
            $event->setResponse($response);
        }
    }
}
