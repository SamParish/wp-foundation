<?php


namespace JB000\WordPress\Factories;


use Carbon\Carbon;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use JB000\WordPress\Builders\TermTaxonomyBuilder;
use JB000\WordPress\Models\TermTaxonomy;
use JB000\WordPress\Repositories\MenuItemHandlerRepository;

class TermFactory extends BaseFactory
{

	protected $menuItemHandlerRepository;

	public function __construct(Repository $cacheRepository, MenuItemHandlerRepository $menuItemHandlerRepository, array $config)
	{
		$this->menuItemHandlerRepository = $menuItemHandlerRepository;
		parent::__construct($cacheRepository, $config);
	}

	/**
	 * Gets the category by id
	 *
	 * @param $id
	 * @return null|TermTaxonomy
	 */
	public function getCategoryById($id)
	{
		$builder = self::builder();

		$builder->category()
		        ->where('term_id',$id)
		        ->limit(1);

		return $this->queryBuilder($builder)->first();
	}


	/**
	 * Gets the category by slug
	 *
	 * @param $slug
	 * @return null|TermTaxonomy
	 */
	public function getCategoryBySlug($slug)
	{
		$builder = self::builder();

		$builder->category()
		        ->slug($slug)
		        ->limit(1);

		return $this->queryBuilder($builder)->first();
	}


	/**
	 * Gets a menu by id
	 *
	 * @param int $id
	 * @return null|TermTaxonomy
	 */
	public function getMenuById($id)
	{
		$builder = self::builder();
		$builder->menu()
		        ->where('term_id',$id)
		        ->menu()
		        ->limit(1);

		return $this->queryBuilder($builder)->first();
	}

	/**
	 * Gets a menu by slug
	 *
	 * @param string $slug
	 *
	 * @return null|TermTaxonomy
	 */
	public function getMenuBySlug($slug)
	{
		$builder = self::builder();
		$builder->slug($slug)
		        ->menu()
		        ->limit(1);

		return $this->queryBuilder($builder)->first();

	}


	/**
	 * Gets all menu items by parent slug.
	 *
	 * Returns a collection of objects with the following fields:
	 *
	 * $id          The menu item id
	 * $menu_order  The order of the menu item
	 * $type        The type of the menu item. E.g. post, category, custom
	 * $object_id   The object id that the menu item.
	 * $parent_id   The menu item id that this item belongs to. Defaults to 0
	 * $url         The url of the menu item. Only applicable for custom type.
	 * $name        The menu item text
	 * $slug        The menu item slug
	 *
	 * @param string $slug
	 * @param null|string   $type
	 * @param int    $limit
	 *
	 * @return Collection
	 */
	public function getMenuItemsBySlug($slug,$type=null,$limit=10)
	{
		$cacheKey = "wp-get-menu-items-by-id-$slug";
		$cacheExpire = Carbon::now()->addMinutes(15);

		if($data = $this->getFromCache($cacheKey))
			return $data;

		$sql = "SELECT 
                t.object_id   AS 'id',
                p.menu_order  AS 'menu_order',
                m1.meta_value AS 'type',
                m2.meta_value AS 'object_id', 
                m3.meta_value AS 'parent_id',
                m4.meta_value AS 'url',
                CASE  
                     WHEN m1.meta_value = 'post' OR m1.meta_value = 'custom' THEN p.post_name
                     WHEN m1.meta_value = 'category' OR m1.meta_value = 'product_cat'  THEN terms.slug
                     WHEN m1.meta_value = 'page' THEN page.post_name
                ELSE
                     m5.meta_value
                END AS 'slug',
                CASE
                     WHEN m1.meta_value = 'category' OR m1.meta_value = 'product_cat'  THEN terms.name
                     WHEN m1.meta_value = 'page' THEN page.post_title
                ELSE 
                        p.post_title
                END AS 'name'
        
                FROM wp_term_relationships t
                LEFT JOIN wp_posts p ON t.object_id = p.id
                LEFT JOIN wp_postmeta m1 ON t.object_id = m1.post_id AND m1.meta_key = '_menu_item_object'
                LEFT JOIN wp_postmeta m2 ON t.object_id = m2.post_id AND m2.meta_key = '_menu_item_object_id'
                LEFT JOIN wp_postmeta m3 ON t.object_id = m3.post_id AND m3.meta_key = '_menu_item_menu_item_parent'
                LEFT JOIN wp_postmeta m4 ON t.object_id = m4.post_id AND m4.meta_key = '_menu_item_url'
                LEFT JOIN wp_postmeta m5 ON t.object_id = m5.post_id AND m5.meta_key = '_menu_item_xfn'
                LEFT JOIN wp_terms terms ON terms.term_id = m2.meta_value
                LEFT JOIN wp_posts page ON m2.meta_value = page.ID
                WHERE term_taxonomy_id = 
                (
                        SELECT taxonomy.term_taxonomy_id FROM wp_term_taxonomy taxonomy
                        LEFT JOIN wp_terms terms ON terms.term_id = taxonomy.term_id
                        WHERE taxonomy.taxonomy = 'nav_menu'
                        AND terms.slug = ?
                )
                AND p.post_type = 'nav_menu_item'
                AND p.post_status = 'publish' ";

		if($type)
			$sql .="AND m1.meta_value = '$type' ";

		$sql .=
			"ORDER BY p.menu_order
                LIMIT $limit";





		$results = \DB::connection('wordpress')->select($sql,[$slug]);


		$collection = new \Illuminate\Support\Collection();

		//run through handlers and add to collection
		foreach($results as $result)
		{
			if ($this->menuItemHandlerRepository)
				$this->menuItemHandlerRepository->runHandlers($result);

			$collection->push($result);
		}

		$this->storeInCache($cacheKey,$collection ,$cacheExpire);
		return $collection;
	}


	/**
	 * @param TermTaxonomyBuilder $builder
	 * @return Collection
	 */
	public function queryBuilder(TermTaxonomyBuilder $builder)
	{
		$cacheKey = 'wp-get-term-taxonomy-by-query-'.$this->getSignature($builder);
		$cacheExpire = Carbon::now()->addMinute(5);
		if($data = $this->getFromCache($cacheKey))
			return $data;

		$results = $builder->get();

		$this->storeInCache($cacheKey,$results,$cacheExpire);
		return $results;
	}

	/**
	 * @return TermTaxonomyBuilder
	 */
	public static function builder()
	{
		return TermTaxonomy::query();
	}


}