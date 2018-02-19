<?php


namespace SamParish\WordPress\Models;
use Illuminate\Support\Collection;
use SamParish\WordPress\Builders\TermTaxonomyBuilder;
use SamParish\WordPress\Traits\CanCastType;


/**
 * Class TermTaxonomy
 *
 * @package JB000\WordPress\Models
 *
 * @property int $termTaxonomyId
 * @property int $termId
 * @property string $taxonomy
 * @property string $description
 * @property int $parent
 * @property int $count
 * @property Term $term
 * @property TermTaxonomy $parentTerm
 * @property Collection $posts
 */
class TermTaxonomy extends BaseModel
{
    use CanCastType;

    protected $table = 'term_taxonomy';
    protected $primaryKey = 'term_taxonomy_id';
    protected $typeAttributeName = 'taxonomy';
    public $timestamps = false;


    /*
     * Load the term by default
     */
    protected $with = [
        'term'
    ];

    protected $casts = [
        'term_taxonomy_id' => 'int',
        'term_id' => 'int',
        'parent' => 'int',
        'count' => 'int'
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentTern()
    {
        return $this->belongsTo(TermTaxonomy::class, 'parent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function children()
    {
        return $this->hasMany(TermTaxonomy::class,'parent','term_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'term_relationships', 'term_taxonomy_id', 'object_id');
    }



    /**
     * Overriding newQuery() to the custom TermTaxonomyBuilder with some interesting methods.
     *
     * @param bool $excludeDeleted
     *
     * @return TermTaxonomyBuilder
     */
    public function newQuery($excludeDeleted = true)
    {
        $builder = new TermTaxonomyBuilder($this->newBaseQueryBuilder());
        $builder->setModel($this)->with($this->with);

        if (isset($this->taxonomy) &&
            !empty($this->taxonomy) &&
            !is_null($this->taxonomy))
        {
            $builder->where('taxonomy', $this->taxonomy);
        }
        return $builder;
    }

}