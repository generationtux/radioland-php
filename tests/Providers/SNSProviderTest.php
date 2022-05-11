<?php

namespace Tests;

use Aws\Sns\SnsClient;
use Aws\Sns\MessageValidator;
use Aws\Sns\Message as SnsMessage;
use Gentux\Radioland\Providers\SNSProvider;
use Gentux\Radioland\MessageHandlerInterface;

class SNSProviderTest extends TestCase
{

    /** @var SNSProvider */
    protected $provider;

    /** @var Mockery\Mock | SnsClient */
    protected $sns;

    /** @var Mockery\Mock | MessageValidator */
    protected $validator;

    /** @var Mockery\Mock | MessageHandlerInterface */
    protected $handler;

    public function setUp()
    {
        $this->sns = Mockery::mock(SnsClient::class);
        $this->validator = Mockery::mock(MessageValidator::class);
        $this->handler = Mockery::mock(MessageHandlerInterface::class);

        $this->provider = new SNSProvider([], $this->handler);
        $this->provider->setClient($this->sns);
        $this->provider->setMessageValidator($this->validator);
    }

    /** @test */
    public function gets_default_config_from_environment()
    {
        putenv('AWS_REGION=us-east-2');
        $provider = new SNSProvider();
        $this->assertSame(['region' => 'us-east-2', 'version' => 'latest'], $provider->config());

        // passed config overrides
        $provider = new SNSProvider(['region' => 'us-west-1', 'version' => '2010-03-31']);
        $this->assertSame(['region' => 'us-west-1', 'version' => '2010-03-31'], $provider->config());
    }

    /** @test */
    public function publishes_json_encoded_message_data()
    {
        $message = new \Gentux\Radioland\Message('arn:foo-topic', ['foo' => 'bar']);

        $expectedMessageConfig = [
            'TopicArn' => 'arn:foo-topic',
            'Message' => '{"foo":"bar"}',
        ];
        $this->sns->shouldReceive('publish')->once()->with($expectedMessageConfig)->andReturn(['MessageId' => 'foo']);

        $result = $this->provider->publish($message);
        $this->assertSame(['MessageId' => 'foo'], $result);
    }

    /** @test */
    public function publishes_multiple_messages()
    {
        $message1 = new \Gentux\Radioland\Message('arn:foo-topic', ['foo' => 'bar']);
        $expectedMessage1 = [
            'TopicArn' => 'arn:foo-topic',
            'Message' => '{"foo":"bar"}',
        ];
        $this->sns->shouldReceive('publish')->once()->with($expectedMessage1)->andReturn(['MessageId' => '1']);

        $message2 = new \Gentux\Radioland\Message('arn:another-topic', ['another' => 'bar']);
        $expectedMessage2 = [
            'TopicArn' => 'arn:another-topic',
            'Message' => '{"another":"bar"}',
        ];
        $this->sns->shouldReceive('publish')->once()->with($expectedMessage2)->andReturn(['MessageId' => '2']);

        $result = $this->provider->publishAll([$message1, $message2]);
        $this->assertSame([
            ['MessageId' => '1'],
            ['MessageId' => '2'],
        ], $result);
    }

    /** @test */
    public function will_confirm_subscription_when_requested()
    {
        $headers = ['x-amz-sns-message-type' => 'SubscriptionConfirmation'];
        $body = json_encode($data = [
            "Type" => "SubscriptionConfirmation",
            "MessageId" => "id-123",
            "Token" => "token-123",
            "TopicArn" => "arn-123",
            "Message" => "foobar",
            "SubscribeURL" => "https://subscribe.com",
            "Timestamp" => "2012-04-26T20:45:04.751Z",
            "SignatureVersion" => "1",
            "Signature" => "sig-123",
            "SigningCertURL" => "https://sign.com"
        ]);

        $this->validator->shouldReceive('validate')->once()->withArgs(function (SnsMessage $arg) use ($data) {
            return $arg->toArray() == $data;
        })->andReturn(true);

        $this->sns->shouldReceive('confirmSubscription')->once()->with([
            'Token' => 'token-123',
            'TopicArn' => 'arn-123'
        ])->andReturn(['provider' => 'message']);

        $result = $this->provider->listen($headers, $body);
        $this->assertSame(['provider' => 'message'], $result);
    }

    /** @test */
    public function will_forward_notifications_to_the_handler()
    {
        $headers = ['x-amz-sns-message-type' => 'SubscriptionConfirmation'];
        $body = json_encode($data = [
            "Type" => "Notification",
            "MessageId" => "id-123",
            "Token" => "token-123",
            "TopicArn" => "arn-123",
            "Message" => "foobar",
            "SubscribeURL" => "https://subscribe.com",
            "Timestamp" => "2012-04-26T20:45:04.751Z",
            "SignatureVersion" => "1",
            "Signature" => "sig-123",
            "SigningCertURL" => "https://sign.com"
        ]);

        $this->validator->shouldReceive('validate')->once()->withArgs(function (SnsMessage $arg) use ($data) {
            return $arg->toArray() == $data;
        })->andReturn(true);

        $this->sns->shouldNotReceive('confirmSubscription');
        $this->handler->shouldReceive('handle')->withARgs(function (\Gentux\Radioland\Message $message) use ($data) {
            return $message->channel() === $data['TopicArn'];
        })->once()->andReturn('whatever');

        $result = $this->provider->listen($headers, $body);
        $this->assertSame('whatever', $result);
    }
}
