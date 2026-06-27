<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(): JsonResponse
    {
        $user  = auth()->user();
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        $budgets = $user->budgets()
            ->with('category')
            ->where('period', 'monthly')
            ->get()
            ->map(function (Budget $budget) use ($user, $start, $end) {
                $spent = $user->transactions()
                    ->where('category_id', $budget->category_id)
                    ->where('type', 'expense')
                    ->whereBetween('date', [$start, $end])
                    ->sum('amount');

                return array_merge($budget->toArray(), [
                    'spent'      => (float) $spent,
                    'percentage' => $budget->amount > 0
                        ? round(($spent / $budget->amount) * 100, 1)
                        : 0,
                ]);
            });

        return response()->json($budgets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'amount'      => 'required|numeric|min:0.01',
            'period'      => 'nullable|in:monthly,weekly',
            'start_date'  => 'nullable|date',
        ]);

        $data['user_id']    = auth()->id();
        $data['period']     = $data['period'] ?? 'monthly';
        $data['start_date'] = $data['start_date'] ?? now()->startOfMonth()->toDateString();

        $budget = Budget::updateOrCreate(
            [
                'user_id'     => $data['user_id'],
                'category_id' => $data['category_id'],
                'period'      => $data['period'],
                'start_date'  => $data['start_date'],
            ],
            ['amount' => $data['amount']]
        );

        return response()->json($budget->load('category'), 201);
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        abort_if($budget->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $budget->update($data);

        return response()->json($budget->load('category'));
    }

    public function destroy(Budget $budget): JsonResponse
    {
        abort_if($budget->user_id !== auth()->id(), 403);
        $budget->delete();

        return response()->json(null, 204);
    }
}
