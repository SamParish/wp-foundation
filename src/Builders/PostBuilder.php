<?php


namespace SamParish\WordPress\Builders;

class PostBuilder extends \Illuminate\Database\Eloquent\Builder
{


    /**
     * Adds a meta query to the post builder.
     *
     * The $alias may be used so the meta row can be used for ordering if necessary
     *
     * If no $metaValue is supplied, then will join the first occurrence of the meta key
     *
     * @param        $alias
     * @param        $metaKey
     * @param string $operator
     * @param null   $metaValue
     * @return $this
     */
    public function addMetaRecord($alias,$metaKey,$operator='=',$metaValue=null)
    {

        $this->leftJoin("postmeta as $alias",function($join) use($alias,$metaKey)
        {
            $join->on("posts.id",'=',"$alias.post_id")
                ->where("$alias.meta_key",$metaKey);
        });

        //add constraint on value if provided
        if($metaValue)
            $this->where("$alias.meta_value",$operator,$metaValue);

        return $this;
    }

	/**
	 * @param $id
	 *
	 * @return PostBuilder
	 */
	public function primaryCategory($id)
	{
		return $this->addMetaRecord('meta_category','_yoast_wpseo_primary_category','=',$id);
	}



    public function status($postStatus)
    {
        return $this->where('posts.post_status', $postStatus);
    }


    public function published()
    {
        return $this->status('publish');
    }


    public function type($type)
    {
        return $this->where('posts.post_type', $type);
    }


    public function typeIn($type)
    {
        if(!is_array($type))
            return $this->type($type);

        return $this->whereIn('posts.post_type', $type);
    }


    public function slug($slug)
    {
        return $this->where('posts.post_name', $slug);
    }

    /**
     * @return self
     */
    public static function instance()
    {
        return \JB000\Wordpress\Models\Post::query();
    }

}
