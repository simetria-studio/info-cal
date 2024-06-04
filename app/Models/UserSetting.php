<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\UserSetting
 *

 *
 * @method static Builder|UserSetting newModelQuery()
 * @method static Builder|UserSetting newQuery()
 * @method static Builder|UserSetting query()
 * @method static Builder|UserSetting whereCreatedAt($value)
 * @method static Builder|UserSetting whereId($value)
 * @method static Builder|UserSetting whereKey($value)
 * @method static Builder|UserSetting whereUpdatedAt($value)
 * @method static Builder|UserSetting whereUserId($value)
 * @method static Builder|UserSetting whereValue($value)
 *
 * @mixin Eloquent
 */
class UserSetting extends Model
{
    use HasFactory;

    protected $table = 'user_settings';

    public static $rules = [
        'key' => 'required',
        'value' => 'required',
        'stripe_key' => 'required',
        'stripe_secret' => 'required',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'key',
        'value',
        'stripe_key',
        'stripe_secret',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'user_id' => 'integer',
        'key' => 'string',
        'value' => 'string',
        'stripe_key' => 'string',
        'stripe_secret' => 'string',
    ];
}
