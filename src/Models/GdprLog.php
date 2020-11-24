<?php

namespace Cerpus\Gdpr\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Cerpus\Gdpr\Models\Scopes\OrderAscendingScope;

class GdprLog extends Model
{
    protected $touches = ['deletionRequest'];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new OrderAscendingScope);

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
        });
    }

    public function deletionRequest()
    {
        return $this->belongsTo(GdprDeletionRequest::class);
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = mb_strtoupper($value);
    }

    public function getStatusAttribute($value)
    {
        return mb_strtoupper($value);
    }
}
