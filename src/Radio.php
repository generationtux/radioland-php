<?php

namespace Gentux\Radioland;

use Gentux\Radioland\Providers\ProviderInterface;

class Radio
{

    /** @var ProviderInterface */
    protected $provider;

    /** @var Message[] */
    protected $collection = [];

    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Publish a new message
     *
     * @param Message $message
     * @return mixed Provider specific result
     */
    public function publish(Message $message)
    {
        return $this->provider->publish($message);
    }

    /**
     * Publish multiple messages
     *
     * @param Message[] $messages
     * @return mixed
     */
    public function publishAll(array $messages)
    {
        return $this->provider->publishAll($messages);
    }

    /**
     * Collect messages to send later
     *
     * @param Message $message
     * @return $this
     */
    public function collect(Message $message)
    {
        $this->collection[] = $message;

        return $this;
    }

    /**
     * Publish all messages in the current collection
     *
     * @return mixed
     */
    public function publishCollection()
    {
        return $this->publishAll($this->collection);
    }
}