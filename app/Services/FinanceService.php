<?php

namespace App\Services;

use App\Models\FinancialPeriod;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use App\Enums\BudgetMode;
use App\Enums\PeriodStatus;
use App\Enums\TransactionType;

class FinanceService
{
    /** Total pemasukan & pengeluaran seluruh transaksi. */
    public function totals(): array
    {
        $incomeEnum = TransactionType::Income->value;
        $expenseEnum = TransactionType::Expense->value;
        $row = DB::table('transactions')->selectRaw("
            COALESCE(SUM(CASE WHEN transactions.type = '{$incomeEnum}'  THEN transactions.amount ELSE 0 END), 0) AS income,
            COALESCE(SUM(CASE WHEN transactions.type = '{$expenseEnum}' THEN transactions.amount ELSE 0 END), 0) AS expense
        ")->first();

        return ['income' => (float) $row->income, 'expense' => (float) $row->expense];
    }

    public function balance(): float
    {
        $t = $this->totals();

        return $t['income'] - $t['expense'];
    }

    public function activePeriod(): ?FinancialPeriod
    {
        return FinancialPeriod::where('is_active', 1)->orderByDesc('id')->first();
    }

    /** Selisih hari dari hari ini ke $date (negatif jika sudah lewat). */
    public function daysUntil(string $date): int
    {
        return (int) (new DateTimeImmutable('today'))
            ->diff(new DateTimeImmutable($date))
            ->format('%r%a');
    }

    /** Payload periode untuk endpoint /api/periods/active. */
    public function periodPayload(?FinancialPeriod $period, float $balance = 0): ?array
    {
        if (!$period) {
            return null;
        }

        $today = new DateTimeImmutable('today');
        $start = new DateTimeImmutable($period->start_date->format('Y-m-d'));
        $end   = new DateTimeImmutable($period->end_date->format('Y-m-d'));

        $sisaHari  = max(0, $this->daysUntil($period->end_date->format('Y-m-d')));
        $isInRange = $today >= $start && $today <= $end;
        $status    = $isInRange ? PeriodStatus::Active->value : ($today > $end ? PeriodStatus::Expired->value : PeriodStatus::Pending->value);

        $dailyBudget = $period->budget_mode === BudgetMode::Auto
            ? ($sisaHari > 0 ? $balance / $sisaHari : 0)
            : (float) $period->daily_budget;

        $payload = $period->toArray();
        $payload['budget_mode']     = $period->budget_mode->value;
        $payload['sisa_hari']       = $sisaHari;
        $payload['periode_aktif']   = $status === 'ACTIVE';
        $payload['periode_status']  = $status;
        $payload['daily_budget']    = round($dailyBudget, 2);

        return $payload;
    }
}
