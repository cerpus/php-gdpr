<?php

namespace Cerpus\Gdpr\Models;

use Illuminate\Database\Eloquent\Model;
use Cerpus\Gdpr\Exceptions\GdprPayloadException;
use Cerpus\Gdpr\Models\Scopes\UpdatedDescendingScope;

class GdprDeletionRequest extends Model
{
    public $incrementing = false;

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(new UpdatedDescendingScope);

        static::deleting(function (GdprDeletionRequest $request) {
            $request->logs()->delete();
        });
    }

    public function logs()
    {
        return $this->hasMany(GdprLog::class);
    }

    public function log($status, $message = null)
    {
        $mostRecent = $this->getMostRecentEvent();

        if ($mostRecent) {
            $mostRecent->touch(); // So we know how long this status was in effect
        }

        $log = new GdprLog();
        $log->status = $status;
        $log->message = $message ? $message : '';
        $log->order = ($mostRecent ?? null) ? ++$mostRecent->order : 1;

        $this->touch(); // Update updated_at on the deletion request so that sorting works as expected

        return $this->logs()->save($log);
    }

    public function getMostRecentEvent()
    {
        $max = $this->load('logs')->logs->max('order');

        // Do this without touching the DB from now on
        if (!$max) {
            return null;
        }

        $position = $this->logs->search(function ($item, $key) use ($max) {
            return $item->order === $max;
        });

        if (is_int($position)) {
            if ($item = $this->logs->slice($position, 1)->first()) {
                return $item;
            }
        }

        return null;
    }

    public function getPayloadAttribute($value)
    {
        return json_decode($value);
    }

    public function setPayloadAttribute($value)
    {
        $newValue = null;

        if (is_object($value)) {
            $newValue = json_encode($value);
        } elseif (is_array($value)) {
            $newValue = json_encode($value);
        } elseif (is_string($value)) {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new GdprPayloadException("Payload is not serializable. [" . json_last_error() . "]" . json_last_error_msg());
            }
            $newValue = $value;
        } else {
            throw new GdprPayloadException("Payload value is not an object, an array or a serializable string.");
        }

        $this->attributes['payload'] = $newValue;
    }
}
