<?php


namespace SamParish\WordPress\Repositories;


use Illuminate\Contracts\Container\Container;

abstract class BaseRepository
{
    /**
     * The registered handlers
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;


    /**
     * @var
     */
    protected $contractType;

    public function __construct(Container $container, $contractType, array $handlers = [])
    {
        $this->contractType = $contractType;
        $this->container = $container;
        foreach($handlers as $k=> $v)
            $this->registerHandler($k,$v);
    }


    /**
     * Registers a handler for a specific short code
     *
     * @param        $name
     * @param string $handler
     * @throws \Exception
     */
    public function registerHandler($name, $handler)
    {
        //class must implement the correct interface
        $reflection = new \ReflectionClass($handler);
        if (!$reflection->implementsInterface($this->contractType))
        {
            throw new \Exception("Class $handler must implement interface ". $this->contractType);
        }
        $this->handlers[$name] = $handler;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function makeHandler($name)
    {
        if(!array_key_exists($name,$this->handlers))
           return null;

        //return instance
        return $this->container->make($this->handlers[$name]);
    }

}