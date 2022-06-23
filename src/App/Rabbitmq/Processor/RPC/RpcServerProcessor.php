<?php

namespace App\Rabbitmq\Processor\RPC;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swarrot\Broker\Message;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;
use Swarrot\Processor\ProcessorInterface;

/**
 * Act as a RPC server when processing am amqp message.
 *
 * @author Baptiste Clavié <clavie.b@gmail.com>
 */
class RpcServerProcessor implements ProcessorInterface
{
    /** @var ProcessorInterface */
    private $processor;

    /** @var MessagePublisherInterface */
    private $publisher;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ProcessorInterface $processor, MessagePublisherInterface $publisher, LoggerInterface $logger = null)
    {
        $this->processor = $processor;
        $this->publisher = $publisher;
        $this->logger    = $logger ?: new NullLogger();
    }

    /** {@inheritdoc} */
    public function process(Message $message, array $options): bool
    {
        $result     = $this->processor->process($message, $options);
        $properties = $message->getProperties();
        if (!isset($properties['reply_to'], $properties['correlation_id']) || empty($properties['reply_to']) || empty($properties['correlation_id'])) {
            return $result;
        }
        if (!$result) {
            return false;
        }
        $body = $this->processor->result();

        // $this->logger->debug('sending a new message', [
        //     'swarrot_processor' => 'rpc',
        //     'queue'             => $properties['reply_to'],
        //     'correlation_id'    => $properties['correlation_id'],
        // ]);

        $message = new Message((string) $body, ['correlation_id' => $properties['correlation_id']]);
        $this->publisher->publish($message, $properties['reply_to']);

        return $result;
    }
}
