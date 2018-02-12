<?php


namespace JB000\WordPress\Models;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JB000\WordPress\Builders\PostBuilder;
use JB000\WordPress\Traits\CanCastType;


/**
 * Class Post
 *
 * @package JB000\WordPress\Models
 *
 * @property int $id
 * @property int $postAuthor
 * @property Carbon $postDate
 * @property Carbon $postDateGmt
 * @property string $postContent
 * @property string $postTitle
 * @property string $postExcerpt
 * @property string $postStatus
 * @property string $commentStatus
 * @property string $pingStatus
 * @property string $postPassword
 * @property string $postName
 * @property string $toPing
 * @property string $pinged
 * @property Carbon $postModified
 * @property Carbon $postModifiedGmt
 * @property int $postParent
 * @property string $guid
 * @property int $menuOrder
 * @property string $postType
 * @property string $postMimeType
 * @property int $commentCount
 * @property string $format
 * @property Collection $meta
 * @property Collection $taxonomies
 */
class Post extends BaseModel
{
    use CanCastType;

    const CREATED_AT = 'post_date';
    const UPDATED_AT = 'post_modified';

	protected $primaryKey = 'ID';
	protected $table = 'posts';
    protected $typeAttributeName = 'post_type';


	protected $dates = [
		'post_date',
		'post_date_gmt',
		'post_modified',
		'post_modified_gmt'
	];

	protected $with = [
		'meta'
	];

	protected $fillable = [
		'post_content',
		'post_title',
		'post_excerpt',
		'post_type',
		'to_ping',
		'pinged',
		'post_content_filtered',
	];



	/**
	 * Get meta fields from the post
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function meta()
	{
		return $this->hasMany(PostMeta::class, 'post_id');
	}


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function taxonomies()
    {
        return $this->belongsToMany(TermTaxonomy::class, 'term_relationships', 'object_id', 'term_taxonomy_id');
    }


    /**
     * Returns a collection of TermTaxonomy which are categories
     *
     * @return Collection
     */
    public function categories()
    {
        return $this->taxonomies
            ->where('taxonomy','category');

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'post_author');
    }

    /**
     * Returns a collection of TermTaxonomy where are tags
     *
     * @return Collection
     */
    public function tags()
    {
        return $this->taxonomies
            ->where('taxonomy','post_tag');
    }

	/**
	 * A nice little feature that basically allows a 'order' to be stored on the post.
	 *
	 *
	 * For example this is very useful if we have a handful of posts (i.e. featured posts) that we would like ordered.
	 *
	 * To add an order to a post, we give the new index as 'null' (inserts at bottom) or 0 (inserts at top)
	 *
	 * To remove the order from a post, we give the $newIndex as PHP_INT_MAX
	 *
	 * Posts can only be ordered with other posts of the same type.
	 *
	 * The 'collection' of posts is defined using the $metaKey. We can keep track of multiple 'collections' by
	 * simply using a different meta key
	 *
	 * @param $metaKey
	 * @param $newIndex
	 */
	public function rearrangeOrder($metaKey,$newIndex)
	{

		//get the current index (referred to as old index)
		$oldIndex = $this->getMeta($metaKey);

		//if we do not have a old index, then we set this to the maximum int value.
		//this will trigger a up operation and will shift down everything below where the position is
		if(!is_numeric($oldIndex))
			$oldIndex = PHP_INT_MAX;

		//base query to get all featured posts and their position by post type
		$baseQuery = Manager::connection('wordpress')->table('postmeta as m')
			->select('m.meta_id', 'm.post_id', 'm.meta_value as position', 'p.post_type')
			->leftJoin('posts as p','p.id','=','m.post_id')
			->where('m.meta_key',$metaKey)
			->whereNotNull('m.meta_value')
			->where('p.post_type',$this->postType);


		//if we do not have a new index, then by default we insert at the bottom
		if(!is_numeric($newIndex))
		{
			$largest = $baseQuery->orderByDesc('m.meta_value')
							->limit(1)
			                ->value('m.meta_value');

			if($largest == null)
				$newIndex = 1;
			else
				$newIndex = $largest+1;
		}

		//determine move up or move down operation
		if($oldIndex == $newIndex)
		{
			//do nothing
			return;
		}

		//lets do some magic
		$operation = ($newIndex > $oldIndex) ? 'down' : 'up';

		if($operation == 'up')
		{
			//move up operation

			/*
			 * Get posts that have the following rules and increase there score by 1:
			 *
			 * $position < $oldIndex
			 * $position >= ($oldIndex-$step)
			 */
			$metas = $baseQuery
			                ->where('m.meta_value','<',$oldIndex)
			                ->where('m.meta_value','>=',$newIndex)
			                ->orderBy('m.meta_value')
			                ->get();

			foreach($metas as $meta)
			{
                Manager::connection('wordpress')->table('postmeta')
				       ->where('meta_id',$meta->meta_id)
				       ->update([
					       'meta_value' => (int)$meta->position+1,
				       ]);
			}
		}
		else
		{
			//move down operation

			/*
			 * Get posts that have the following rules and decrease there score by 1
			 *
			 * $position > $oldIndex
			 * $position <= ($oldIndex+$step)
			 */

			$metas = $baseQuery
			                ->where('m.meta_value','>',$oldIndex)
			                ->where('m.meta_value','<=',$newIndex)
			                ->orderBy('m.meta_value')
			                ->get();

			foreach($metas as $meta)
			{
                Manager::connection('wordpress')->table('postmeta')
				       ->where('meta_id',$meta->meta_id)
				       ->update([
					       'meta_value' => ((int)$meta->position-1),
				       ]);
			}
		}

		if($newIndex == PHP_INT_MAX)
		{
			//delete entry
            Manager::connection('wordpress')->table('postmeta')
				->where('post_id',$this->id)
				->where('meta_key',$metaKey)
				->delete();
		}
		else
		{
			//update / insert new index
			$existing = Manager::connection('wordpress')->table('postmeta')
				            ->where('post_id',$this->id)
				            ->where('meta_key',$metaKey)
				            ->count() > 0;

			if($existing)
			{
                Manager::connection('wordpress')->table('postmeta')
				       ->where('post_id',$this->id)
				       ->where('meta_key',$metaKey)
				       ->update([
					       'meta_value' => $newIndex
				       ]);
			}
			else
			{
                Manager::connection('wordpress')->table('postmeta')
					->insert([
						'post_id' => $this->id,
						'meta_key' => $metaKey,
						'meta_value' => $newIndex,
					]);
			}
		}
	}

	/**
	 * Getter for the 'id' attribute
	 *
	 * @return int|null
	 */
	public function getIdAttribute()
	{
		if(array_key_exists('ID',$this->attributes))
			return $this->attributes['ID'];
		return null;
	}


    /**
     * Overriding newQuery() to the custom PostBuilder with some interesting methods.
     *
     * @param bool $excludeDeleted
     *
     * @return PostBuilder
     */
    public function newQuery($excludeDeleted = true)
    {
        $builder = new PostBuilder($this->newBaseQueryBuilder());
        $builder->setModel($this)->with($this->with);
        // disabled the default orderBy because else Post::all()->orderBy(..)
        // is not working properly anymore.
        // $builder->orderBy('post_date', 'desc');

        if ($excludeDeleted and $this->softDelete) {
            $builder->whereNull($this->getQualifiedDeletedAtColumn());
        }

        return $builder;
    }

    /**
     * Determines if post has a excerpt supplied
     *
     * @return bool
     */
    public function hasExcerpt()
    {
        return strlen($this->postExcerpt)>0;
    }

	/**
	 * Determines if the post has the meta key supplied
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function hasMeta($key)
	{
		return $this->meta->where('meta_key',$key)->count() >0;
	}

	/**
	 * Gets the value of the first matching key from the meta data.
	 *
	 * @param      $key
	 * @param bool $default
	 *
	 * @return mixed
	 */
	public function getMeta($key, $default=null)
	{
		$meta = $this->meta->where('meta_key',$key)->first();
		if($meta !== null)
		{
			$value = $meta->meta_value;

			//check if value needs un serialising
			if($this->isSerialized($value))
				$value = unserialize($value);

			//check if the meta key has been defined as a cast
			if ($this->hasCast($key)) {
				return $this->castAttribute($key, $value);
			}

			return $value;
		}
		return $default;
	}

	/**
	 * Saves meta to the post.
	 *
	 * If $single = true, will overwrite existing key if it exists
	 *
	 * @param      $key
	 * @param      $value
	 * @param bool $single
	 */
	public function storeMeta($key,$value,$single=true)
	{

		//the post must exist before we can save meta data
		if(!$this->exists)
			$this->save();



		/*
		 * To keep compatibility with eloquent, we run the value through mutators.
		 *
		 * Currently we do not support any json casting.
		 */

		// First we will check for the presence of a mutator for the set operation
		// which simply lets the developers tweak the attribute as it is set on
		// the model, such as "json_encoding" an listing of data for storage.
		if ($this->hasSetMutator($key))
		{
			$method = 'set'.Str::studly($key).'Attribute';

			$value = $this->{$method}($value);
		}

		// If an attribute is listed as a "date", we'll convert it from a DateTime
		// instance into a form proper for storage on the database tables using
		// the connection grammar's date format. We will auto set the values.
		elseif ($value && $this->isDateAttribute($key))
		{
			$value = $this->fromDateTime($value);
		}


		//if the value is an array, or an object then it will need serialising
		if(is_object($value) || is_array($value))
			$value = serialize($value);



		//if single then we want to update if we already have the meta
		if($single)
		{
			$existing = $this->meta()->where('meta_key',$key)->first();
			if($existing)
			{
				//update existing
				$existing->metaValue = $value;
				$existing->save();
				return;
			}
		}

		//create meta
		$this->meta()->create([
			'meta_value' => $value,
			'meta_key' => $key,
		]);
	}


    /**
     * Gets the post format
     *
     * @return false|string
     */
	public function getFormatAttribute()
    {
        $taxonomy = $this->taxonomies
            ->where('taxonomy', 'post_format')
            ->first();

        if ($taxonomy and $taxonomy->term) {
            return str_replace('post-format-', '', $taxonomy->term->slug);
        }
        return false;
    }



	/**
	 * Magic method to return the meta data like the post original fields.
	 *
	 * All keys are converted to snake case.
	 *
	 * Any value is automatically de serialised
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function __get($key)
	{
		//First check to see if the base model will return anything
		if (($value = parent::__get($key)) !== null) {
			return $value;
		}

		//before returning any meta then the post must be saved
		if(!$this->exists)
			return null;

		//we store meta keys as snake case
		$key = snake_case($key);

		//ensure we have a meta with this value set
		if($this->hasMeta($key))
		{
			return $this->getMeta( $key );
		}
		return null;
	}


	/**
	 * Taken from the wordpress core.
	 *
	 * //todo move to helper class
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	protected function isSerialized( $data ) {
	    // if it isn't a string, it isn't serialized
	    if ( !is_string( $data ) )
	        return false;
	    $data = trim( $data );
	    if ( 'N;' == $data )
	        return true;
	    if ( !preg_match( '/^([adObis]):/', $data, $badions ) )
	        return false;
	    switch ( $badions[1] ) {
	        case 'a' :
	        case 'O' :
	        case 's' :
	            if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) )
	                return true;
	            break;
	        case 'b' :
	        case 'i' :
	        case 'd' :
	            if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) )
		            return true;
	            break;
	    }
	    return false;
	}


	/**
	 * Saves the post and also any additional attributes that have been set.
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function save( array $options = [] )
	{

		/*
		 * When we set attributes which are not part of the original fields, we expect these to be saved in the meta table.
		 *
		 * To do this we need to get all attributes that are dirty and not part of the original fields.
		 *
		 */
		$changes = $this->getDirty();

		//exclude all changes part of the original fields
		foreach($changes as $k=>$v)
		{
			//exclude all changes part of the original fields
			if(array_key_exists($k,$this->original))
			{
				continue;
			}

			//store change in meta field
			$this->storeMeta($k,$v);

			//remove the key from attributes so does not get saved to posts table
			//the key will be in the meta() section so we can still access it
			unset($this->attributes[$k]);
		}


		return parent::save( $options ); // TODO: Change the autogenerated stub
	}


	/**
	 * @param $obj
	 *
	 * @return self
	 */
	public static function hinted($obj)
	{
		return $obj;
	}

}