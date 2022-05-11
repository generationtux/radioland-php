<?php

use Gentux\Radioland\Radio;
use Gentux\Radioland\Laravel\PublishCollectionAfterResponse;

class PublishCollectionAfterResponseTest extends TestCase
{

    /** @var Mockery\Mock | Radio */
    protected $radio;

    /** @var PublishCollectionAfterResponse */
    protected $middleware;

    public function setUp()
    {
        $this->radio = Mockery::mock(Radio::class);
        $this->middleware = new PublishCollectionAfterResponse($this->radio);
    }

    /** @test */
    public function publishes_collection_on_terminate()
    {
        $this->radio->shouldReceive('publishCollection')->once()->andReturn('result');
        $result = $this->middleware->terminate(null, null);
        $this->assertSame('result', $result);
    }
}
