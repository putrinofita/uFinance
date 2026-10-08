<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionType;

class StatsController extends Controller
{
    use RespondsWithJson;

    /** Whitelist format DATE_FORMAT (MySQL) per periode. */
    private const FORMATS = [
        'daily'   => '%Y-%m-%d',
        'monthly' => '%Y-%m',
        'yearly'  => '%Y',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $period = (string) $request->query('period', 'daily');

        if (!isset(self::FORMATS[$period])) {
            return $this->error('Period harus daily, monthly, atau yearly.', 422);
        }

        $format = self::FORMATS[$period];

        $series = DB::table('transactions')
            ->selectRaw("
                DATE_FORMAT(date, '{$format}') AS label,
                COALESCE(SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense
            ")
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $categories = DB::table('transactions as t')
            ->leftJoin('categories as c', 'c.id', '=', 't.category_id')
            ->where('t.type', TransactionType::Expense->value)
            ->selectRaw("COALESCE(c.name, 'Uncategorized') AS category, COALESCE(SUM(t.amount), 0) AS total")
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return $this->success([
            'series'     => $series,
            'categories' => $categories,
        ], 'Statistik berhasil dimuat.');
    }
}
