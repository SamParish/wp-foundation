<?php


namespace SamParish\WordPress\Models;


/**
 * Class TermMeta
 *
 * @package JB000\WordPress\Models
 *
 * @property int metaId
 * @property int termId
 * @property string $metaKey
 * @property string $metaValue
 */
class TermMeta extends BaseModel
{
    protected $table = 'termmeta';
    public $timestamps = false;

    protected $fillable = [
        'meta_key',
        'meta_value',
        'term_id'
    ];

    protected $casts = [
        'metaId' => 'int',
        'termId' => 'int'
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function term()
    {
        return $this->hasOne(Term::class,'term_id','term_id');
    }
}