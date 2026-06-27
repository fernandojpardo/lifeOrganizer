<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Support\Carbon;

class FinanceController extends Controller
{
    public function dashboardSummary()
    {
        $user  = auth()->user();
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        $netWorth    = (float) $user->accounts()->sum('balance');
        $monthIncome = (float) $user->transactions()->where('type', 'income')->whereBetween('date', [$start, $end])->sum('amount');
        $monthExpense = (float) $user->transactions()->where('type', 'expense')->whereBetween('date', [$start, $end])->sum('amount');

        // Weekly debt obligation: remaining / weeks until due_date
        $activeDebts = $user->debts()->where('remaining_amount', '>', 0)->get();
        $weeklyDebtTotal = $activeDebts->sum(function ($debt) {
            if (!$debt->due_date) return $debt->remaining_amount / 4;
            $weeksLeft = max(1, (int) ceil(now()->diffInDays($debt->due_date, false) / 7));
            return $debt->remaining_amount / $weeksLeft;
        });

        // Weekly cashflow buckets for this month
        $cashflowWeekly = [];
        for ($w = 0; $w < 4; $w++) {
            $wStart = $start->copy()->addWeeks($w);
            $wEnd   = $wStart->copy()->addDays(6)->min($end);

            $cashflowWeekly[] = [
                'week'    => $w + 1,
                'label'   => 'Week ' . ($w + 1),
                'income'  => (float) $user->transactions()->where('type', 'income')->whereBetween('date', [$wStart, $wEnd])->sum('amount'),
                'expense' => (float) $user->transactions()->where('type', 'expense')->whereBetween('date', [$wStart, $wEnd])->sum('amount'),
                'debt'    => round((float) $weeklyDebtTotal, 2),
            ];
        }

        // Debts summary for dashboard widget
        $debtsSummary = $activeDebts->map(function ($debt) {
            $weeksLeft = $debt->due_date
                ? max(1, (int) ceil(now()->diffInDays($debt->due_date, false) / 7))
                : 4;
            return [
                'id'             => $debt->id,
                'name'           => $debt->name,
                'creditor'       => $debt->creditor,
                'remaining'      => (float) $debt->remaining_amount,
                'weekly_payment' => round((float) $debt->remaining_amount / $weeksLeft, 2),
                'weeks_left'     => $weeksLeft,
                'due_date'       => $debt->due_date?->toDateString(),
            ];
        })->values();

        // Budget alerts (>= 70%)
        $budgets = $user->budgets()->with('category')->where('period', 'monthly')->get();
        $budgetAlerts = $budgets->map(function (Budget $b) use ($user, $start, $end) {
            $spent = (float) $user->transactions()->where('category_id', $b->category_id)->where('type', 'expense')->whereBetween('date', [$start, $end])->sum('amount');
            $pct   = $b->amount > 0 ? round(($spent / $b->amount) * 100, 1) : 0;
            return array_merge($b->toArray(), ['spent' => $spent, 'percentage' => $pct]);
        })->filter(fn ($b) => $b['percentage'] >= 70)->sortByDesc('percentage')->take(5)->values();

        $savingGoals = $user->savingGoals()->get();
        $savingsSummary = $savingGoals->map(fn ($g) => [
            'id'             => $g->id,
            'name'           => $g->name,
            'current_amount' => (float) $g->current_amount,
            'target_amount'  => (float) $g->target_amount,
            'target_date'    => $g->target_date?->toDateString(),
            'percentage'     => $g->target_amount > 0 ? round(($g->current_amount / $g->target_amount) * 100, 1) : 0,
        ])->values();

        return [
            'net_worth'           => $netWorth,
            'month_income'        => $monthIncome,
            'month_expense'       => $monthExpense,
            'cashflow_weekly'     => $cashflowWeekly,
            'budget_alerts'       => $budgetAlerts,
            'accounts'            => $user->accounts()->orderByDesc('is_default')->get(),
            'debts_summary'       => $debtsSummary,
            'total_debt'          => (float) $activeDebts->sum('remaining_amount'),
            'total_weekly_debt'   => round((float) $weeklyDebtTotal, 2),
            'savings_summary'     => $savingsSummary,
            'total_savings'       => (float) $savingGoals->sum('current_amount'),
            'total_savings_target'=> (float) $savingGoals->sum('target_amount'),
        ];
    }


    public function summary()
    {
        $user = auth()->user();

        $totalIncome = $user->incomes()->sum('amount');
        $totalExpenses = $user->expenses()->sum('amount');
        $totalSavings = $user->savingGoals()->sum('current_amount');
        $totalDebt = $user->debts()->sum('remaining_amount');

        $savingGoals = $user->savingGoals()->get();
        $debts = $user->debts()->get();

        return [
            'total_income' => (float) $totalIncome,
            'total_expenses' => (float) $totalExpenses,
            'total_savings' => (float) $totalSavings,
            'total_debt' => (float) $totalDebt,
            'balance' => (float) ($totalIncome - $totalExpenses - $totalDebt),
            'saving_goals_count' => $savingGoals->count(),
            'debts_count' => $debts->count(),
        ];
    }

    public function projection()
    {
        $user = auth()->user();
        $months = 12;

        $incomes = $user->incomes()->get();
        $savingGoals = $user->savingGoals()->get();
        $debts = $user->debts()->get();

        $monthlyIncome = $incomes->sum('amount');
        $savingAllocations = $savingGoals->sum('monthly_percentage');
        $debtAllocations = $debts->sum('monthly_percentage');

        $projection = [];

        for ($month = 1; $month <= $months; $month++) {
            $availableIncome = $monthlyIncome;

            $savingThisMonth = [];
            foreach ($savingGoals as $goal) {
                $allocation = $goal->monthly_percentage;
                $amount = ($monthlyIncome * $allocation) / 100;
                $savingThisMonth[] = [
                    'goal_id' => $goal->id,
                    'goal_name' => $goal->name,
                    'allocation' => (float) $allocation,
                    'amount' => (float) $amount,
                ];
            }

            $debtThisMonth = [];
            foreach ($debts as $debt) {
                $allocation = $debt->monthly_percentage;
                $amount = ($monthlyIncome * $allocation) / 100;
                $debtThisMonth[] = [
                    'debt_id' => $debt->id,
                    'debt_name' => $debt->name,
                    'allocation' => (float) $allocation,
                    'amount' => (float) $amount,
                    'remaining' => max(0, (float) $debt->remaining_amount - ($amount * $month)),
                ];
            }

            $projection[] = [
                'month' => $month,
                'income' => (float) $monthlyIncome,
                'saving_goals' => $savingThisMonth,
                'debts' => $debtThisMonth,
                'total_allocated' => (float) (($savingAllocations + $debtAllocations) * $monthlyIncome / 100),
                'remaining' => (float) ($monthlyIncome - (($savingAllocations + $debtAllocations) * $monthlyIncome / 100)),
            ];
        }

        return $projection;
    }
}
