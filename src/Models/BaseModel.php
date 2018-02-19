<?php


namespace SamParish\WordPress\Models;


use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{

    protected $connection = 'wordpress';


    /**
     * An array of handlers that have been run on the model
     *
     * @var array
     */
    public $handlers = [];


    /**
     * Ensures that a handler has been ran on the model
     *
     * @param $class
     * @throws \Exception
     */
    public function requireHandler($class)
    {
        if(!in_array($class,$this->handlers))
            throw new \Exception("Handler $class has not been ran");
    }

	/**
	 * Enable camel casing for wordpress
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		if($key == 'ID')
			$key = 'id';
		$key = snake_case($key);
		return parent::getAttribute($key);
	}

	/**
	 * Enable Camel Casing for wordpress
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return $this
	 */
	public function setAttribute($key, $value)
	{
		if($key == 'ID')
			$key = 'id';
		$key = snake_case($key);
		return parent::setAttribute($key, $value);
	}
}