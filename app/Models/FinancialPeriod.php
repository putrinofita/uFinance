<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BudgetMode;
use App\Models\Concerns\SerializesPlainDates;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Concerns\BelongsToUser;

#[Fillable([
    'start_date', 'end_date', 'total_days', 'budget_mode',
    'daily_budget', 'linked_income_id', 'is_active', 'user_id'
])]
class FinancialPeriod extends Model
{
    protected $table = 'financial_periods';

    use SerializesPlainDates, BelongsToUser;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'budget_mode' => BudgetMode::class,
            'daily_budget' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function linkedIncome(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'linked_income_id');
    }
}
