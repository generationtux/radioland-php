<?php

namespace Gentux\Radioland\Providers;

use Gentux\Radioland\Message;
use Gentux\Radioland\MessageHandlerInterface;

interface ProviderInterface
{

    /**
     * @param array                        $config Provider configuration
     * @param MessageHandlerInterface|null $handler
     */
    public function __construct(array $config, $handler);

    /**
     * @param Message $message
     * @return mixed
     */
    public function publish(Message $message);

    /**
     * @param Message[] $message
     * @return mixed
     */
    public function publishAll(array $message);

    /**
     * @param array  $headers HTTP headers sent with message notification
     * @param string $body HTTP body sent with message notification
     */
    public function listen(array $headers, $body);
}
