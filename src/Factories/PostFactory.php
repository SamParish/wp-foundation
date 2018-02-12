<?php


namespace JB000\WordPress\Factories;

use Carbon\Carbon;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use JB000\WordPress\Builders\PostBuilder;
use JB000\WordPress\Models\Post;
use JB000\WordPress\Repositories\PostHandlerRepository;

class PostFactory extends BaseFactory
{

	protected $postHandlerRepository;

	public function __construct(Repository $cacheRepository, PostHandlerRepository $postHandlerRepository, array $config)
	{
		$this->postHandlerRepository = $postHandlerRepository;
		parent::__construct($cacheRepository, $config);
	}

	/**
	 * @param $id
	 * @return Post|mixed|null
	 */
	public function getById($id)
	{

		$cacheKey = "wp-get-post-$id";
		$cacheExpire = Carbon::now()->addMinute(5);

		if($data = $this->getFromCache($cacheKey))
			return $data;

		$post = Post::find($id);

		//run handlers
		if($post)
			$this->runHandlers($post);

		$this->storeInCache($cacheKey,$post,$cacheExpire);

		return $post;
	}

	/**
	 * @param PostBuilder $builder
	 * @return Collection
	 */
	public function queryBuilder(PostBuilder $builder)
	{

		$cacheKey = 'wp-get-posts-by-query-'.$this->getSignature($builder);
		$cacheExpire = Carbon::now()->addMinute(5);

		if($data = $this->getFromCache($cacheKey))
			return $data;


		$posts = $builder->get();

		//run handlers
		foreach($posts as $post)
			$this->runHandlers($post);

		$this->storeInCache($cacheKey,$posts,$cacheExpire);

		return $posts;
	}

	/**
	 * @param PostBuilder $builder
	 * @return int
	 */
	public function queryBuilderCount(PostBuilder $builder)
	{
		$cacheKey = 'wp-get-count-by-query-'.$this->getSignature($builder);
		$cacheExpire = Carbon::now()->addMinute(5);
		if($data = $this->getFromCache($cacheKey))
			return $data;


		$count = $builder->count();
		$this->storeInCache($cacheKey,$count,$cacheExpire);
		return $count;
	}


	/**
	 * Gets the most popular posts
	 *
	 * @param array|string $postTypes
	 * @param int   $limit
	 * @param null  $primaryCategoryId
	 * @param array $excludeIds
	 * @return Collection
	 */
	public function getPopular($postTypes, $limit=10, $primaryCategoryId = null, $excludeIds = [])
	{

		if (!is_array($postTypes))
			$postTypes = [$postTypes];

		$cacheKey = "wp-post-factory-get-popular--";
		if (count($postTypes) > 0)
		{
			foreach ($postTypes as $postType)
				$cacheKey .= $postType . '-';
		}

		//we use 2 -- to separate the primary category id from exclude ids
		$cacheKey .= "$primaryCategoryId--";

		if (count($excludeIds) > 0)
		{
			foreach ($excludeIds as $excludeId)
				$cacheKey .= $excludeId . '-';

		}
		$cacheExpire = Carbon::now()->addMinute(5);


		//check cache
		if($data = $this->getFromCache($cacheKey))
			return $data;

		if(!is_array($postTypes))
			$postTypes = [$postTypes];

		$query = \DB::connection('wordpress')
		            ->table('postmeta as m1')
		            ->select('posts.*')
		            ->leftJoin('posts','posts.id','=','m1.post_id')
		            ->where('m1.meta_key',$this->getMetaKeyFromConfig('popular_rank','_popular_rank'))
		            ->orderByDesc('m1.meta_value')
		            ->whereIn('posts.post_type',$postTypes)
					->where('posts.post_status','publish')
					->distinct('m1.post_id')
		            ->limit($limit);


		//exclude any ids if supplied
		if(count($excludeIds)>0)
			$query->whereNotIn('m1.post_id', $excludeIds);

		//filter by category
		if($primaryCategoryId != null)
		{
			$query->rightJoin('postmeta as m2',function($join) use($primaryCategoryId)
			{
				$join->on('m2.post_id','=','m1.post_id')
				     ->where('m2.meta_key','=',$this->getMetaKeyFromConfig('primary_category','_primary_category'))
				     ->where('m2.meta_value','=',$primaryCategoryId);
			});
		}

		$results = Post::hydrate($query->get()->toArray());


		//run through parsers
		foreach($results as $result)
			$this->runHandlers($result);

		//store in cache
		$this->storeInCache($cacheKey,$results,$cacheExpire);

		return $results;

	}


	/**
	 * Returns the related posts.
	 *
	 * @param Post $post
	 * @param int  $limit
	 *
	 * @return Collection
	 */
	public function getRelated(Post $post,$limit=10)
	{
		$cacheKey = "wp-post-factory-get-related-{$post->id}-$limit";
		$cacheExpire = Carbon::now()->addMinute(5);

		if($data = $this->getFromCache($cacheKey))
			return $data;

		$posts = new Collection();

		if($tag = $post->tags()->first())
		{
			$posts = $tag->posts()
							->whereNotIn('id', [$post->id])
			                ->orderBy('post_date', 'desc')
			                ->limit($limit)
			                ->get();
		}

		//run through handlers
		foreach($posts as $post)
			$this->runHandlers($post);

		$this->storeInCache($cacheKey,$posts,$cacheExpire);

		return $posts;
	}



	/**
	 * @param array|string $postTypes
	 * @param int   $limit
	 * @param null  $primaryCategoryId
	 * @param array $excludeIds
	 * @return Collection
	 */
	public function getLatest($postTypes, $limit=10, $primaryCategoryId=null, $excludeIds = [])
	{
		$builder = self::builder();
		$builder->status('publish')
		        ->typeIn($postTypes)
		        ->limit($limit)
		        ->orderByDesc('posts.post_date');

		//exclude any id's if supplied
		if(count($excludeIds) >0)
			$builder->whereNotIn('posts.id',$excludeIds);

		// filter by primary category if category is supplied
		// Yoast SEO plugin required for wordpress
		if($primaryCategoryId != null)
			$builder->addMetaRecord('m1',$this->getMetaKeyFromConfig('primary_category','_primary_category_id'),'=',$primaryCategoryId);

		return $this->queryBuilder($builder);
	}


	/**
	 * Gets posts marked as featured.
	 *
	 *
	 * @param array|string $postTypes
	 * @param int          $limit
	 * @param null         $primaryCategoryId
	 * @param array        $excludeIds
	 * @return Collection
	 */
	public function getFeatured($postTypes, $limit=10, $primaryCategoryId=null, $excludeIds = [])
	{

		$builder = self::builder();
		$builder->addMetaRecord('featured',$this->getMetaKeyFromConfig('featured','_featured'),'=','1')
		        ->addMetaRecord('position',$this->getMetaKeyFromConfig('featured_position','_featured_position'))
		        ->typeIn($postTypes)
		        ->published()
		        ->orderBy('position.meta_value')
		        ->limit($limit);

		//exclude any id's if supplied
		if(count($excludeIds) >0)
			$builder->whereNotIn('posts.id',$excludeIds);

		// filter by primary category if category is supplied
		// Yoast SEO plugin required for wordpress
		if($primaryCategoryId != null)
			$builder->addMetaRecord('m1',$this->getMetaKeyFromConfig('primary_category','_primary_category_id'),'=',$primaryCategoryId);

		return $this->queryBuilder($builder);
	}

	/**
	 *
	 * Will return value from config at wordpress.meta_keys.{$key}
	 *
	 * @param $key
	 * @param $default
	 * @return mixed
	 */
	protected function getMetaKeyFromConfig($key,$default)
	{
		return $this->config['meta_keys'][$key] ?? $default;

	}

	/**
	 * Run handlers on post
	 *
	 * @param Post $post
	 */
	protected function runHandlers(Post $post)
	{
		if($this->postHandlerRepository != null)
			$this->postHandlerRepository->runHandlers($post);
	}

	/**
	 * @return PostBuilder
	 */
	public static function builder()
	{
		return Post::query();
	}


}