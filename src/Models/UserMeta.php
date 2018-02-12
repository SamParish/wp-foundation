<?php


namespace JB000\WordPress\Models;

/**
 * Class UserMeta
 *
 * @package JB000\WordPress\Models
 *
 * @property int $umetaId
 * @property int $userId
 * @property string $metaKey
 * @property string $metaValue
 */
class UserMeta extends BaseModel
{
    protected $table = 'usermeta';
    protected $primaryKey = 'umeta_id';
    public $timestamps = false;

    protected $fillable = [
        'meta_key',
        'meta_value',
        'user_id'
    ];

    /**
     * User relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


}

