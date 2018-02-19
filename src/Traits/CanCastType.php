<?php


namespace SamParish\WordPress\Traits;


trait CanCastType
{
    /**
     * @var array
     */
    protected static $types = [];

    /**
     * Register your Type classes here to have them be instantiated instead of the standard base model.
     *
     * This method allows you to register classes that will be used for specific model types.
     *
     * The value in the $typeAttributeName property will determine which attribute to use to decide the model type.
     *
     * It will use the value of this attribute for the key in the static::$types array.
     *
     * If an entry is found then will attempt to cast the base model to the class defined.
     *
     * It is important that the supplied class extends the base model.
     *
     * E.g
     *
     * To have a post model with post_type = 'video' get automatically cast to a Video::class.
     *
     * self::$typeAttributeName = 'post_type'
     * self::registerType('video',Video::class)
     *
     * ---
     *
     * To have a TermTaxonomy model return as a Category::class
     *
     * self::$typeAttributeName = 'taxonomy'
     * self::registerType('category',Category::class)
     *
     * @param string $name  The name of the type (e.g. 'post', 'page', 'custom_post_type')
     * @param string $class The class that represents the type model (e.g. 'Post', 'Page', 'CustomPostType')
     */
    public static function registerType($name, $class)
    {
        static::$types[$name] = $class;
    }


    /**
     * Overrides default behaviour by instantiating class based on the $attributes->{$this->typeAttributeName} value.
     *
     * By default, this method will always return an instance of the calling class. However if types have
     * been registered with the class using the registerType() static method, this will now return an
     * instance of that class instead.
     *
     *
     * @param array $attributes
     * @param null  $connection
     *
     * @return mixed
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {

        if(empty($this->typeAttributeName))
        {
            $class = get_called_class();
        }
        elseif (is_object($attributes) && array_key_exists($attributes->{$this->typeAttributeName}, static::$types))
        {
            $class = static::$types[$attributes->{$this->typeAttributeName}];
        }
        elseif (is_array($attributes) && array_key_exists($attributes[$this->typeAttributeName], static::$types))
        {
            $class = static::$types[$attributes[$this->typeAttributeName]];
        }
        else {
            $class = get_called_class();
        }

        $model = new $class([]);
        $model->exists = true;

        $model->setRawAttributes((array) $attributes, true);
        $model->setConnection($connection ?: $this->connection);

        return $model;

    }
}