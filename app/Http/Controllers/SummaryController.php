<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\Transaction;
use App\Services\FinanceService;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\TransactionType;
use App\Enums\BudgetMode;
use App\Enums\PeriodStatus;

class SummaryController extends Controller
{
    use RespondsWithJson;

    public function __invoke(Request $request, FinanceService $finance): JsonResponse
    {
        ['income' => $income, 'expense' => $expense] = $finance->totals();
        $balance = $income - $expense;

        $today = Carbon::today();
        $todayExpense = (float) Transaction::where('type', TransactionType::Expense->value)
            ->where('date', $today->toDateString())
            ->sum('amount');

        $calendarRemainingDays = $today->daysInMonth - $today->day + 1;
        $requestedDays = (int) $request->query('days', 0);
        $remainingDays = $requestedDays > 0 ? $requestedDays : $calendarRemainingDays;
        $dailyBudget   = $remainingDays > 0 ? $balance / $remainingDays : $balance;

        $activePeriod = $finance->activePeriod();
        $period       = null;
        $periodStatus = PeriodStatus::NoPeriod->value;
        $alertStatus  = PeriodStatus::NoPeriod->value;

        if ($activePeriod) {
            $now   = new DateTimeImmutable('today');
            $start = new DateTimeImmutable($activePeriod->start_date->format('Y-m-d'));
            $end   = new DateTimeImmutable($activePeriod->end_date->format('Y-m-d'));

            $periodRemainingDays = max(0, $finance->daysUntil($activePeriod->end_date->format('Y-m-d')));
            $periodStatus = ($now >= $start && $now <= $end) ? PeriodStatus::Active->value : ($now > $end ? PeriodStatus::Expired->value : PeriodStatus::Pending->value);
            $periodDailyBudget = $activePeriod->budget_mode === BudgetMode::Auto
                ? ($periodRemainingDays > 0 ? $balance / $periodRemainingDays : 0)
                : (float) $activePeriod->daily_budget;

            $period = [
                'id'               => (int) $activePeriod->id,
                'start_date'       => $activePeriod->start_date->format('Y-m-d'),
                'end_date'         => $activePeriod->end_date->format('Y-m-d'),
                'total_days'       => (int) $activePeriod->total_days,
                'sisa_hari'        => $periodRemainingDays,
                'budget_mode'      => $activePeriod->budget_mode->value,
                'daily_budget'     => round($periodDailyBudget, 2),
                'linked_income_id' => $activePeriod->linked_income_id === null ? null : (int) $activePeriod->linked_income_id,
                'periode_aktif'    => $periodStatus === 'ACTIVE',
                'periode_status'   => $periodStatus,
            ];

            $dailyBudget   = $periodDailyBudget;
            $remainingDays = $periodRemainingDays;
            $alertStatus   = $periodStatus === PeriodStatus::Active->value
                ? ($todayExpense > $periodDailyBudget ? 'WARNING' : 'SAFE')
                : $periodStatus;
        } else {
            $dailyBudget   = 0;
            $remainingDays = 0;
        }

        return $this->success([
            'saldo'                   => round($balance, 2),
            'total_income'            => round($income, 2),
            'total_expense'           => round($expense, 2),
            'income'                  => round($income, 2),
            'expense'                 => round($expense, 2),
            'balance'                 => round($balance, 2),
            'remaining_days'          => $remainingDays,
            'calendar_remaining_days' => $calendarRemainingDays,
            'is_custom_days'          => $requestedDays > 0,
            'daily_budget'            => round($dailyBudget, 2),
            'period'                  => $period,
            'sisa_hari'               => $remainingDays,
            'budget_mode'             => $period['budget_mode'] ?? null,
            'period_status'           => $periodStatus,
            'today_expense'           => round($todayExpense, 2),
            'alert_status'            => $alertStatus,
            'status_today'            => $alertStatus === 'WARNING' ? 'warning' : ($alertStatus === 'SAFE' ? 'safe' : 'no_period'),
        ], 'Ringkasan berhasil dihitung.');
    }
}
