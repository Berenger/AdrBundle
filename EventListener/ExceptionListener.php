<?php

namespace AdrBundle\EventListener;

use AdrBundle\Exceptions\ErrorApiException;
use AdrBundle\Response\ContentNegotiator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class ExceptionListener
 * @package AppBundle\EventListener
 */
class ExceptionListener implements EventSubscriberInterface
{
    /** @var ContentNegotiator */
    protected $negotiator;

    /** @var LoggerInterface */
    protected $logger;

    /** @var bool */
    protected $isDebug;

    /**
     * ExceptionListener constructor.
     * @param ContentNegotiator $negotiator
     * @param LoggerInterface $logger
     * @param KernelInterface $kernel
     */
    public function __construct(
        ContentNegotiator $negotiator,
        LoggerInterface $logger,
        KernelInterface $kernel
    ) {
        $this->isDebug = $kernel->isDebug();
        $this->negotiator = $negotiator;
        $this->logger = $logger;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * @param ExceptionEvent $event
     * @throws \Exception
     */
    public function onKernelException(ExceptionEvent $event)
    {
        if ($this->isDebug) {
            return;
        }

        $event->allowCustomResponseCode();
        $exception = $event->getThrowable();
        $message = $exception->getMessage();
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        } elseif ($exception instanceof ErrorApiException) {
            $statusCode = $exception->getStatusCode();
            $exceptionData = $exception->getData();
        } else {
            $statusCode = $exception->getCode();

        }

        if (!in_array($statusCode, array_keys(Response::$statusTexts))) {
            $statusCode = 500;
        }

        $classError = get_class($exception);
        $namespaceClassError = explode('\\', $classError);

        if ($namespaceClassError[0] == 'Doctrine') {
            $message = 'Database error';
        }

        if (!isset($exceptionData)) {
            $data = [
                'data' => [
                    'code' => $statusCode,
                    'status' => Response::$statusTexts[$statusCode],
                    'message' => isset($message) ? $message : $exception->getMessage(),
                ],
            ];
        } else {
            $data = [
                'data' => [
                    'code' => $statusCode,
                    'status' => Response::$statusTexts[$statusCode],
                    'message' => isset($message) ? $message : $exception->getMessage(),
                    'data' => $exceptionData,
                ],
            ];
        }

        $this->logger->error(sprintf(
            '%s %s in file %s at line %s',
            $statusCode,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ));

        $event->setResponse($this->negotiator->negotiate($data, $statusCode));
    }
}
