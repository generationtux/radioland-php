<?php

namespace Gentux\Radioland\Providers;

use Aws\Sns\SnsClient;
use Aws\Sns\MessageValidator;
use Gentux\Radioland\Message;
use Aws\Sns\Message as SnsMessage;
use Gentux\Radioland\MessageHandlerInterface;

class SNSProvider implements ProviderInterface
{

    /** @var SnsClient */
    protected $client;

    /** @var array */
    protected $config;

    /** @var MessageHandlerInterface */
    protected $handler;

    /** @var MessageValidator */
    protected $validator;

    public function __construct(array $config = [], $handler = null)
    {
        $this->config = array_merge([
            'region' => getenv('AWS_REGION') ?: '',
            'version' => 'latest',
        ], $config);

        $this->client = new SnsClient($this->config);

        if ($handler) {
          if (! $handler instanceof MessageHandlerInterface) {
            throw new \Exception('Message handler should implement Gentux\Radioland\MessageHandlerInterface');
          }
          $this->handler = $handler;
        }

        $this->validator = new MessageValidator();
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
     * Listen for messages and handle appropriately.
     *
     * @param array                   $headers HTTP headers sent with message notification
     * @param string                  $body HTTP body sent with message notification
     * @return mixed
     * @throws \Exception
     */
    public function listen(array $headers, $body)
    {
        $data = json_decode($body, true);
        if ($data === null) {
            throw new \Exception('Unable to decode JSON body.');
        }

        $snsMessage = new SnsMessage($data);
        $this->validator->validate($snsMessage);

        if ($data['Type'] === 'SubscriptionConfirmation') {
            return $this->client->confirmSubscription([
                'TopicArn' => $data['TopicArn'],
                'Token' => $data['Token'],
            ]);
        }

        if ($data['Type'] === 'Notification') {
            if (!$this->handler) return null;
            $message = new Message($data['TopicArn'], json_decode($data['Message'], true));
            return $this->handler->handle($message);
        }

        if ($data['Type'] === 'UnsubscribeConfirmation') {
            return null;
        }

        throw new \Exception('Invalid message received.');
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

    /**
     * @param MessageValidator $validator
     */
    public function setMessageValidator(MessageValidator $validator)
    {
        $this->validator = $validator;
    }
}
