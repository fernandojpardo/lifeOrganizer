<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Income;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index()
    {
        return auth()->user()->incomes()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:weekly,biweekly,monthly',
            'next_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        return auth()->user()->incomes()->create($validated);
    }

    public function show(Income $income)
    {
        $this->authorize('view', $income);
        return $income;
    }

    public function update(Request $request, Income $income)
    {
        $this->authorize('update', $income);

        $validated = $request->validate([
            'amount' => 'numeric|min:0',
            'frequency' => 'in:weekly,biweekly,monthly',
            'next_date' => 'date',
            'description' => 'nullable|string',
        ]);

        $income->update($validated);
        return $income;
    }

    public function destroy(Income $income)
    {
        $this->authorize('delete', $income);
        $income->delete();
        return response()->noContent();
    }
}
