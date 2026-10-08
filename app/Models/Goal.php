<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoalStatus;
use App\Models\Concerns\SerializesPlainDates;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'target_amount', 'current_amount', 'deadline', 'status'])]
class Goal extends Model
{
    use SerializesPlainDates;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'deadline' => 'date:Y-m-d',
            'status' => GoalStatus::class,
        ];
    }
}
