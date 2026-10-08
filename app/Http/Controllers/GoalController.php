<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\GoalStatus;

class GoalController extends Controller
{
    use RespondsWithJson;

    public function index(): JsonResponse
    {
        $goals = Goal::orderBy('status')
            ->orderByRaw('deadline IS NULL ASC')
            ->orderBy('deadline')
            ->orderByDesc('id')
            ->get();

        return $this->success($goals, 'Goals berhasil dimuat.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request->all(), [
            'name'           => ['required', 'string', 'max:150'],
            'target_amount'  => ['required', 'numeric', 'min:0'],
            'current_amount' => ['nullable', 'numeric', 'min:0'],
            'deadline'       => ['nullable', 'date_format:Y-m-d'],
        ]);

        $current = isset($data['current_amount']) ? (float) $data['current_amount'] : 0.0;
        $target  = (float) $data['target_amount'];

        $goal = Goal::create([
            'name'           => $data['name'],
            'target_amount'  => $target,
            'current_amount' => $current,
            'deadline'       => $data['deadline'] ?? null,
            'status'         => $this->status($current, $target),
        ]);

        return $this->success(['id' => $goal->id], 'Goal ditambahkan.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $goal = Goal::find($id);
        if (!$goal) {
            return $this->error('Goal tidak ditemukan.', 404);
        }

        $data = $this->validated($request->all(), [
            'name'           => ['sometimes', 'required', 'string', 'max:150'],
            'target_amount'  => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'current_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'top_up'         => ['sometimes', 'nullable', 'numeric'],
            'deadline'       => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'status'         => ['sometimes', 'nullable', 'in:active,achieved'],
        ]);

        $target = isset($data['target_amount']) ? (float) $data['target_amount'] : (float) $goal->target_amount;

        if (isset($data['top_up'])) {
            $current = (float) $goal->current_amount + (float) $data['top_up'];
        } elseif (isset($data['current_amount'])) {
            $current = (float) $data['current_amount'];
        } else {
            $current = (float) $goal->current_amount;
        }

        $goal->update([
            'name'           => $data['name'] ?? $goal->name,
            'target_amount'  => $target,
            'current_amount' => $current,
            'deadline'       => array_key_exists('deadline', $data) ? $data['deadline'] : $goal->deadline,
            'status'         => $data['status'] ?? $this->status($current, $target),
        ]);

        return $this->success(['id' => $id], 'Goal diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $goal = Goal::find($id);
        if (!$goal) {
            return $this->error('Goal tidak ditemukan.', 404);
        }

        $goal->delete();

        return $this->success(['id' => $id], 'Goal dihapus.');
    }

    private function status(float $current, float $target): string
    {
        return $target > 0 && $current >= $target ? GoalStatus::Achieved->value : GoalStatus::Active->value;
    }
}
