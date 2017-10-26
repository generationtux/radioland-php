<?php

namespace Gentux\Radioland;

interface MessageHandlerInterface
{

  public function handle(Message $message);
}
