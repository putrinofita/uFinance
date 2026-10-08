<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionType;

class CalendarController extends Controller
{
    use RespondsWithJson;

    public function __invoke(Request $request): JsonResponse
    {
        $month = (string) $request->query('month', date('Y-m'));

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $this->error('Format month harus YYYY-MM.', 422);
        }

        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $incomeEnum = TransactionType::Income->value;
        $expenseEnum = TransactionType::Expense->value;
        $rows = DB::table('transactions')
            ->selectRaw("
                transactions.date,
                COALESCE(SUM(CASE WHEN transactions.type = '{$incomeEnum}'  THEN transactions.amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN transactions.type = '{$expenseEnum}' THEN transactions.amount ELSE 0 END), 0) AS total_expense,
                COALESCE(SUM(CASE WHEN transactions.type = '{$incomeEnum}' THEN transactions.amount WHEN transactions.type = '{$expenseEnum}' THEN -transactions.amount ELSE 0 END), 0) AS net
            ")
            ->whereBetween('transactions.date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('transactions.date')
            ->orderBy('date')
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $income  = (float) $row->total_income;
            $expense = (float) $row->total_expense;
            $data[$row->date] = [
                'total_income'  => round($income, 2),
                'total_expense' => round($expense, 2),
                'net'           => round((float) $row->net, 2),
                'has_income'    => $income > 0,
                'has_expense'   => $expense > 0,
            ];
        }

        return $this->success($data, 'Data kalender berhasil dimuat.');
    }
}
