<?php

use Aws\Sns\SnsClient;
use Gentux\Radioland\Providers\SNSProvider;

class SNSProviderTest extends TestCase
{

    /** @var SNSProvider */
    protected $provider;

    /** @var Mockery\Mock | SnsClient */
    protected $sns;

    public function setUp()
    {
        $this->sns = Mockery::mock(SnsClient::class);
        $this->provider = new SNSProvider();
        $this->provider->setClient($this->sns);
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
}
