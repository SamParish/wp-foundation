<?php


namespace JB000\WordPress\Models;

/**
 * Class Options
 *
 * @package JB000\WordPress\Models
 *
 * @property int $optionId
 * @property string $optionName
 * @property string $optionValue
 * @property bool $autoload
 */
class Option extends BaseModel
{
    const CREATED_AT = null;
    const UPDATED_AT = null;


    protected $table = 'options';
    protected $primaryKey = 'option_id';

    protected $fillable = [
        'option_name',
        'option_value',
        'autoload',
    ];

    protected $casts = [
        'option_id' => 'int',
        'autoload' => 'bool'
    ];


    /**
     * Getter for the 'optionValue' attribute
     *
     * @return string|null
     */
    public function getOptionValueAttribute()
    {
    	if(!array_key_exists('option_value',$this->attributes))
	        return null;

        try {

            $value = unserialize($this->attributes['option_value']);

			// if we get false, but the original value is not false then something has gone wrong.
	        // return the option_value as is instead of unserializing
	        // added this to handle cases where unserialize doesn't throw an error that is catchable
	        if($value === false && $this->attributes['option_value'] !== false)
	        {
	        	return $this->attributes['option_value'];
	        }
	        else
	        {
	            return $value;
	        }

        }
        catch (\Exception $ex)
        {
            return $this->attributes['option_value'];
        }
    }

}
