<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\FinancialPeriod;
use App\Services\FinanceService;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\PeriodStatus;

class PeriodController extends Controller
{
    use RespondsWithJson;

    public function __construct(private FinanceService $finance)
    {
    }

    /** GET /api/periods/active */
    public function showActive(): JsonResponse
    {
        $period = $this->finance->periodPayload($this->finance->activePeriod(), $this->finance->balance());

        return $this->success($period ?: [
            'periode_aktif'  => false,
            'periode_status' => PeriodStatus::NoPeriod->value,
            'sisa_hari'      => 0,
            'daily_budget'   => 0,
        ], $period ? 'Periode aktif berhasil dimuat.' : 'Tidak ada periode aktif.');
    }

    /** PUT /api/periods/active */
    public function updateActive(Request $request): JsonResponse
    {
        $existing = $this->finance->activePeriod();
        if (!$existing) {
            return $this->error('Tidak ada periode aktif untuk diedit.', 404);
        }

        $data  = array_merge($existing->toArray(), $request->all());
        $valid = $this->validatePeriod($data, true);

        $existing->update($valid);

        return $this->success(['id' => (int) $existing->id], 'Periode aktif diperbarui.');
    }

    /** DELETE /api/periods/active */
    public function closeActive(): JsonResponse
    {
        $closed = FinancialPeriod::where('is_active', 1)->update(['is_active' => 0]);

        return $this->success(['closed' => $closed], 'Periode aktif ditutup.');
    }

    /** POST /api/periods — buat periode baru, periode lama otomatis ditutup. */
    public function store(Request $request): JsonResponse
    {
        $valid = $this->validatePeriod($request->all());

        $period = DB::transaction(function () use ($valid) {
            FinancialPeriod::where('is_active', 1)->update(['is_active' => 0]);

            return FinancialPeriod::create($valid + ['is_active' => 1]);
        });

        return $this->success(['id' => (int) $period->id], 'Periode baru dibuat.', 201);
    }

    private function validatePeriod(array $data, bool $partial = false): array
    {
        if (!$partial) {
            foreach (['start_date', 'end_date', 'budget_mode'] as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $this->fail("Field {$field} wajib diisi.", 422);
                }
            }
        }

        $start = $data['start_date'] ?? null;
        $end   = $data['end_date'] ?? null;
        $mode  = $data['budget_mode'] ?? 'auto';
        $dailyBudget = (($data['daily_budget'] ?? '') !== '')
            ? (float) $data['daily_budget']
            : null;

        if (!in_array($mode, ['auto', 'manual'], true)) {
            $this->fail('Mode budget harus auto atau manual.', 422);
        }

        if (!$start || !$end) {
            $this->fail('Tanggal mulai dan tanggal akhir wajib diisi.', 422);
        }

        $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $start);
        $endDate   = DateTimeImmutable::createFromFormat('Y-m-d', $end);
        if (!$startDate || !$endDate) {
            $this->fail('Format tanggal harus YYYY-MM-DD.', 422);
        }

        $totalDays = (int) $startDate->diff($endDate)->format('%r%a');
        if ($totalDays <= 0) {
            $this->fail('Tanggal akhir harus setelah tanggal mulai.', 422);
        }

        if ($mode === 'manual') {
            if ($dailyBudget === null || $dailyBudget <= 0) {
                $this->fail('Daily budget manual wajib diisi dan lebih dari 0.', 422);
            }
        } else {
            $dailyBudget = null;
        }

        return [
            'start_date'       => $start,
            'end_date'         => $end,
            'total_days'       => $totalDays,
            'budget_mode'      => $mode,
            'daily_budget'     => $dailyBudget,
            'linked_income_id' => empty($data['linked_income_id']) ? null : (int) $data['linked_income_id'],
        ];
    }
}
