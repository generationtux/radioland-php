<?php

namespace Gentux\Radioland\Providers;

use Gentux\Radioland\Message;

interface ProviderInterface
{

    public function __construct(array $config);

    public function publish(Message $message);

    /**
     * @param Message[] $message
     * @return mixed
     */
    public function publishAll(array $message);
}