<?php

namespace Gentux\Radioland\Providers;

use Aws\Sns\SnsClient;
use Gentux\Radioland\Message;

class SNSProvider implements ProviderInterface
{

    /** @var SnsClient */
    protected $client;

    /** @var array */
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'region' => getenv('AWS_REGION') ?: '',
            'version' => 'latest',
        ], $config);

        $this->client = new SnsClient($this->config);
    }

    /**
     * Publish a message to SNS
     *
     * @param Message $message
     * @return mixed
     */
    public function publish(Message $message)
    {
        $config = [
            'TopicArn' => $message->channel(),
            'Message' => json_encode($message->data()),
        ];

        return $this->client->publish($config);
    }

    /**
     * Publish multiple messages to SNS
     *
     * @param Message[] $messages
     * @return mixed
     */
    public function publishAll(array $messages)
    {
        $results = [];
        foreach ($messages as $message) {
            $results[] = $this->publish($message);
        }

        return $results;
    }

    /**
     * Get the SNS client
     *
     * @return SnsClient
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Manually set SNS client
     *
     * @param SnsClient $client
     * @return $this
     */
    public function setClient(SnsClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get the client configuration
     *
     * @return array
     */
    public function config()
    {
        return $this->config;
    }
}