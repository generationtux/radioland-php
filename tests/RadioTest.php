<?php

namespace Tests;

use Gentux\Radioland\Radio;
use Gentux\Radioland\Message;
use Gentux\Radioland\Providers\ProviderInterface;

class RadioTest extends TestCase
{

    /** @var Mockery\Mock | ProviderInterface */
    protected $provider;

    /** @var Radio */
    protected $radio;

    public function setUp()
    {
        $this->provider = Mockery::mock(ProviderInterface::class);
        $this->radio = new Radio($this->provider);
    }

    /** @test */
    public function publishes_messages_to_the_provider()
    {
        $message = new Message('foo-channel', ['foo' => 'data']);
        $this->provider->shouldReceive('publish')->once()->with($message)->andReturn('provider-result');

        $result = $this->radio->publish($message);
        $this->assertSame('provider-result', $result);
    }

    /** @test */
    public function publish_multiple_messages()
    {
        $message1 = new Message('channel-1', ['data' => '2']);
        $message2 = new Message('channel-1', ['data' => '1']);

        $this->provider->shouldReceive('publishAll')->once()->with([$message1, $message2])->andReturn(['provider-results']);

        $result = $this->radio->publishAll([$message1, $message2]);
        $this->assertSame(['provider-results'], $result);
    }

    /** @test */
    public function collect_messages_and_publish_all_at_once()
    {
        $message1 = new Message('channel-1', ['data' => '2']);
        $message2 = new Message('channel-1', ['data' => '1']);

        $this->provider->shouldReceive('publishAll')->once()->with([$message1, $message2])->andReturn(['provider-results']);

        $result = $this->radio->collect($message1);
        $this->assertSame($this->radio, $result);

        $this->radio->collect($message2);
        $result = $this->radio->publishCollection();
        $this->assertSame(['provider-results'], $result);
    }
}
