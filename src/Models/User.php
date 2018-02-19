<?php


namespace SamParish\WordPress\Models;
use Carbon\Carbon;

/**
 * Class User
 *
 * @package JB000\WordPress\Models
 *
 * @property int $id
 * @property string $userLogin
 * @property string $userPass
 * @property string $userNicename
 * @property string $userEmail
 * @property string $userUrl
 * @property Carbon $userRegistered
 * @property string $userActivationKey
 * @property int $userStatus
 * @property string $displayName
 */
class User extends BaseModel
{

    const CREATED_AT = 'user_registered';
    const UPDATED_AT = null;

    protected $table = 'users';
    protected $connection = 'wordpress';
    protected $primaryKey = 'ID';
    protected $hidden = ['user_pass'];
    protected $dates = ['user_registered'];
    protected $with = ['meta'];

    /**
     * Setter for the 'updated_at' attribute
     *
     * @param $value
     */
    public function setUpdatedAtAttribute($value)
    {
        //do nothing here
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function meta()
    {
        return $this->hasMany(UserMeta::class,'user_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'post_author');
    }

}

