<?php

namespace Gentux\Radioland\Laravel;

use Closure;
use Gentux\Radioland\Radio;

class PublishCollectionAfterResponse
{

    /** @var Radio */
    protected $radio;

    public function __construct(Radio $radio)
    {
        $this->radio = $radio;
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        return $this->radio->publishCollection();
    }
}