<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 16/02/2017
 * Time: 3:12 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Url extends Model
{
    protected $fillable = [
        'full_path', 'status'
    ];

    protected $appends = [
        'domainFullPath', 'urls'
    ];

    /**
     * relationship with url
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sites()
    {
        return $this->hasMany('App\Models\Site', 'url_id', 'id');
    }

    /**
     * relationship with item
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany('App\Models\Item', 'url_id', 'id');
    }

    /**
     * relationship with domain
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function domain()
    {
        return $this->belongsTo('App\Models\Domain', 'domain_id', 'id');
    }

    /*----------------------------------------------------------------------*/
    /* Attributes                                                           */
    /*----------------------------------------------------------------------*/
    /**
     * Get Domain
     * @return mixed
     */
    public function getDomainFullPathAttribute()
    {
        $urlSegments = parse_url($this->full_path);
        return "{$urlSegments['scheme']}://{$urlSegments['host']}";
    }

    /**
     * Get related path to interact with Url object
     * @return array
     */
    public function getUrlsAttribute()
    {
        return [
            'show' => route('url.show', $this->getKey()),
            'store' => route('url.store'),
            'edit' => route('url.edit', $this->getKey()),
            'update' => route('url.update', $this->getKey()),
            'delete' => route('url.destroy', $this->getKey()),
        ];
    }
}