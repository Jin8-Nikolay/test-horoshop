<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\LoginAlreadyTakenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $debug,
        private readonly string $apiPathPrefix,
    ) {
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), $this->apiPathPrefix)) {
            return;
        }

        $throwable = $event->getThrowable();
        if ($throwable instanceof AccessDeniedException || $throwable instanceof AuthenticationException) {
            return;
        }

        $validation = $this->extractValidationFailure($throwable);
        if ($validation) {
            $event->setResponse(new JsonResponse([
                'error' => 'Validation failed.',
                'violations' => $this->formatViolations($validation),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        if ($throwable instanceof LoginAlreadyTakenException) {
            $event->setResponse(new JsonResponse(['error' => $throwable->getMessage()], Response::HTTP_CONFLICT));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            $event->setResponse(new JsonResponse(
                ['error' => $throwable->getMessage() ?: (Response::$statusTexts[$status] ?? 'Error')],
                $status,
                $throwable->getHeaders(),
            ));

            return;
        }

        $payload = ['error' => 'Internal Server Error.'];
        if ($this->debug) {
            $payload['message'] = $throwable->getMessage();
            $payload['exception'] = $throwable::class;
        }

        $event->setResponse(new JsonResponse($payload, Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    private function extractValidationFailure(Throwable $throwable): ?ValidationFailedException
    {
        if ($throwable instanceof ValidationFailedException) {
            return $throwable;
        }

        $previous = $throwable->getPrevious();

        return $previous instanceof ValidationFailedException ? $previous : null;
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    private function formatViolations(ValidationFailedException $exception): array
    {
        $violations = [];
        foreach ($exception->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => (string)$violation->getMessage(),
            ];
        }

        return $violations;
    }
}
