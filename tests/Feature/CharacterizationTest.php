<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Goal;
use App\Models\FinancialPeriod;
use App\Models\RecurringTransaction;

class CharacterizationTest extends TestCase
{
    public function test_capture_json()
    {
        // Seed some data first
        Category::firstOrCreate(['name' => 'Food']);
        $cat = Category::first();
        
        Transaction::create([
            'type' => 'income',
            'amount' => 5000,
            'date' => '2026-10-01',
            'category_id' => $cat->id,
            'note' => 'test income'
        ]);
        
        Transaction::create([
            'type' => 'expense',
            'amount' => 1000,
            'date' => date('Y-m-d'),
            'category_id' => $cat->id,
            'note' => 'test expense'
        ]);

        Goal::create([
            'name' => 'Test Goal',
            'target_amount' => 10000,
            'current_amount' => 5000,
            'deadline' => '2026-12-31',
            'status' => 'active'
        ]);

        FinancialPeriod::create([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'total_days' => 31,
            'budget_mode' => 'auto',
            'is_active' => 1
        ]);

        RecurringTransaction::create([
            'type' => 'expense',
            'amount' => 500,
            'interval_days' => 7,
            'start_date' => '2026-10-01',
            'category_id' => $cat->id,
            'note' => 'test recurring'
        ]);

        $endpoints = [
            '/api/summary',
            '/api/stats?period=daily',
            '/api/periods/active',
            '/api/goals',
            '/api/transactions',
            '/api/recurring',
            '/api/categories',
            '/api/calendar?month=2026-10'
        ];

        $results = [];
        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            $results[$endpoint] = json_decode($response->getContent(), true);
        }

        // Test POST/PUT responses
        $response = $this->postJson('/api/goals', [
            'name' => 'New Goal',
            'target_amount' => 2000,
        ]);
        $results['POST /api/goals'] = json_decode($response->getContent(), true);

        $response = $this->putJson('/api/goals/1', [
            'top_up' => 500
        ]);
        $results['PUT /api/goals/1'] = json_decode($response->getContent(), true);

        file_put_contents(base_path('json_old.json'), json_encode($results, JSON_PRETTY_PRINT));
        $this->assertTrue(true);
    }
}
