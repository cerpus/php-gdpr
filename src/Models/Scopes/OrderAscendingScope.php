<?php

namespace Cerpus\Gdpr\Models\Scopes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class OrderAscendingScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        return $builder->orderBy('order');
    }
}
