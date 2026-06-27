<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index()
    {
        return auth()->user()->debts()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'remaining_amount' => 'required|numeric|min:0',
            'monthly_percentage' => 'required|numeric|min:0|max:100',
            'creditor' => 'nullable|string',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        return auth()->user()->debts()->create($validated);
    }

    public function show(Debt $debt)
    {
        $this->authorize('view', $debt);
        return $debt;
    }

    public function update(Request $request, Debt $debt)
    {
        $this->authorize('update', $debt);

        $validated = $request->validate([
            'name' => 'string',
            'total_amount' => 'numeric|min:0',
            'remaining_amount' => 'numeric|min:0',
            'monthly_percentage' => 'numeric|min:0|max:100',
            'creditor' => 'nullable|string',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $debt->update($validated);
        return $debt;
    }

    public function destroy(Debt $debt)
    {
        $this->authorize('delete', $debt);
        $debt->delete();
        return response()->noContent();
    }
}
