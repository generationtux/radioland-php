<?php

namespace Gentux\Radioland;

class Message
{

    /** @var string */
    protected $channel;

    /** @var mixed */
    protected $data;

    /**
     * @param string $channel
     * @param mixed $data
     */
    public function __construct($channel = '', $data = [])
    {
        $this->channel = $channel;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function channel()
    {
        return $this->channel;
    }

    /**
     * @return mixed
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @param string $channel
     * @return $this
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}