<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\SavingGoal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingGoalController extends Controller
{
    public function index()
    {
        return auth()->user()->savingGoals()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'target_amount' => 'required|numeric|min:0',
            'monthly_percentage' => 'required|numeric|min:0|max:100',
            'target_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        return auth()->user()->savingGoals()->create($validated);
    }

    public function show(SavingGoal $savingGoal)
    {
        $this->authorize('view', $savingGoal);
        return $savingGoal;
    }

    public function update(Request $request, SavingGoal $savingGoal)
    {
        $this->authorize('update', $savingGoal);

        $validated = $request->validate([
            'name' => 'string',
            'target_amount' => 'numeric|min:0',
            'current_amount' => 'numeric|min:0',
            'monthly_percentage' => 'numeric|min:0|max:100',
            'target_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $savingGoal->update($validated);
        return $savingGoal;
    }

    public function deposit(Request $request, SavingGoal $savingGoal)
    {
        $this->authorize('update', $savingGoal);

        $validated = $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'account_id' => 'required|integer|exists:accounts,id',
        ]);

        $user    = auth()->user();
        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        DB::transaction(function () use ($savingGoal, $validated, $user, $account) {
            Transaction::create([
                'user_id'     => $user->id,
                'account_id'  => $account->id,
                'type'        => 'expense',
                'amount'      => $validated['amount'],
                'date'        => now()->toDateString(),
                'description' => 'Savings deposit: ' . $savingGoal->name,
            ]);
            $account->decrement('balance', $validated['amount']);
            $savingGoal->increment('current_amount', $validated['amount']);
        });

        return $savingGoal->fresh();
    }

    public function destroy(SavingGoal $savingGoal)
    {
        $this->authorize('delete', $savingGoal);
        $savingGoal->delete();
        return response()->noContent();
    }
}
