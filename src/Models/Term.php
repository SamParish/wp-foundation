<?php


namespace SamParish\WordPress\Models;


/**
 * Class Term
 *
 * @package SamParish\WordPress\Models
 *
 * @property int $termId
 * @property string $name
 * @property string $slug
 * @property int $termGroup
 */
class Term extends BaseModel
{

    protected $table = 'terms';
    protected $primaryKey = 'term_id';
    public $timestamps = false;

    protected $casts = [
        'term_id' => 'int',
        'term_group' => 'int',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function taxonomy()
    {
        return $this->hasOne(TermTaxonomy::class, 'term_id');
    }
}
