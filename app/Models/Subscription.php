<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 6/02/2017
 * Time: 7:57 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use IvanCLI\Chargify\Chargify;

class Subscription extends Model
{
    protected $fillable = [
        'token', 'api_subscription_id'
    ];

    protected $appends = [
        'isValid', 'apiSubscription', 'isTrialing', 'isPastDue', 'isActive'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    /*----------------------------------------------------------------------*/
    /* Attributes                                                           */
    /*----------------------------------------------------------------------*/

    /**
     * Get subscription details from API
     * @return \IvanCLI\Chargify\Models\Subscription|null
     */
    public function getApiSubscriptionAttribute()
    {
        return Chargify::subscription()->get($this->api_subscription_id);
    }

    /**
     * attribute showing subscription state
     * @return bool
     */
    public function getIsTrialingAttribute()
    {
        if (!is_null($this->apiSubscription)) {
            return $this->apiSubscription->state == 'trialing';
        }
        return false;
    }

    /**
     * attribute showing subscription state
     * @return bool
     */
    public function getIsPastDueAttribute()
    {
        if (!is_null($this->apiSubscription)) {
            return $this->apiSubscription->state == 'past_due';
        }
        return false;
    }

    /**
     * attribute showing subscription state
     * @return bool
     */
    public function getIsActiveAttribute()
    {
        if (!is_null($this->apiSubscription)) {
            return $this->apiSubscription->state == 'active';
        }
        return false;
    }

    /**
     * Check if subscription is valid
     * @return bool
     */
    public function getIsValidAttribute()
    {
        if (is_null($this->apiSubscription)) {
            return false;
        }
        return $this->isTrialing || $this->isActive;
    }

    /* ---------------------------------------------------------------------- */
    /* helper functions */
    /* ---------------------------------------------------------------------- */

    /**
     * set token to subscription
     *
     * @param $token
     * @return mixed
     */
    public function setToken($token)
    {
        $this->token = $token;
        $this->save();
        return $this->token;
    }
}