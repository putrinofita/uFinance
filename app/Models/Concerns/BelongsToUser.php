<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

trait BelongsToUser
{
    /**
     * Boot the trait and apply the global scope and creating event.
     */
    protected static function bootBelongsToUser(): void
    {
        if (auth()->check()) {
            static::addGlobalScope('user_id', function (Builder $builder) {
                $builder->where($builder->getModel()->getTable() . '.user_id', auth()->id());
            });
        }

        static::creating(function (Model $model) {
            if (auth()->check() && empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });
    }

    /**
     * Get the user that owns the model.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
