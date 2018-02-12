<?php


namespace JB000\Wordpress\Factories;


use Carbon\Carbon;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseFactory
{

    /**
     * @var Repository
     */
    protected $cacheRepository;


    /**
     * @var array
     */
    protected $config = [];


    /**
     * @var bool
     */
    protected $useCache = true;


    public function __construct(Repository $cacheRepository, array $config)
    {
        $this->cacheRepository = $cacheRepository;

        $this->config = $config;

        if(array_key_exists('use_cache',$config) && is_bool($config['use_cache']))
            $this->useCache = $config['use_cache'];

    }

    /**
     * Disables the built in cache
     */
    public function disableCache()
    {
        $this->useCache  = false;

    }

    /**
     * Enables the built in cache
     */
    public function enableCache()
    {
        $this->useCache  = true;
    }


    /**
     * Gets a unique signature from the builder
     *
     * @param Builder $builder
     * @return string
     */
    protected function getSignature(Builder $builder)
    {
        return md5($builder->toSql().serialize($builder->getBindings()));
    }


    /**
     * Gets data from cache if enabled.
     *
     * @param $key
     * @return false|mixed
     */
    protected function getFromCache($key)
    {
        if($this->useCache && $this->cacheRepository &&  $this->cacheRepository->has($key))
            return $this->cacheRepository->get($key);

        return false;
    }

    /**
     * Stores data in cache if enabled
     *
     * @param        $key
     * @param        $data
     * @param Carbon|null $lifetime
     */
    protected function storeInCache($key,$data,$lifetime)
    {
        if($this->useCache && $this->cacheRepository)
        {
            $this->cacheRepository->put($key,$data,$lifetime);
        }
    }


}