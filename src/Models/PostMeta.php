<?php


namespace SamParish\WordPress\Models;

/**
 * Class PostMeta
 *
 * @package SamParish\WordPress\Models
 *
 * @property int $metaId
 * @property int $postId
 * @property string $metaKey
 * @property string $metaValue
 */
class PostMeta extends BaseModel
{

	protected $primaryKey = 'meta_id';
	protected $table = 'postmeta';
	public $timestamps = false;
	protected $fillable = [
		'post_id',
		'meta_key',
		'meta_value'
	];

	/**
	 * Post relationship.
	 *
	 * If $ref = true, then will assume this meta record is referencing another post and will use the meta_value to return the psot.
	 *
	 * @param bool $ref
	 * @return HasOne
	 */
	public function post($ref = false)
	{
		if ($ref)
		{
			$this->primaryKey = 'meta_value';
			return $this->hasOne(Post::class, 'id');
		}
		return $this->hasOne(Post::class, 'id','post_id');
	}
}
