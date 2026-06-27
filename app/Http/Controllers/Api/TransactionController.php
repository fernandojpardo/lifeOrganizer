<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\CategorySuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transactions = auth()->user()
            ->transactions()
            ->with(['account', 'toAccount', 'category'])
            ->filter($request)
            ->latest('date')
            ->orderByDesc('id')
            ->paginate(30);

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id'    => 'required|integer|exists:accounts,id',
            'to_account_id' => 'nullable|integer|exists:accounts,id|different:account_id',
            'category_id'   => 'nullable|integer|exists:categories,id',
            'type'          => 'required|in:income,expense,transfer,adjustment',
            'amount'        => 'required|numeric|min:0.01',
            'date'          => 'required|date',
            'description'   => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'is_recurring'  => 'nullable|boolean',
        ]);

        // Verify ownership of accounts
        $user = auth()->user();
        $account = Account::where('id', $data['account_id'])->where('user_id', $user->id)->firstOrFail();

        $toAccount = null;
        if (!empty($data['to_account_id'])) {
            $toAccount = Account::where('id', $data['to_account_id'])->where('user_id', $user->id)->firstOrFail();
        }

        // Auto-suggest category if not provided
        if (empty($data['category_id']) && !empty($data['description'])) {
            $suggested = CategorySuggester::suggest($data['description']);
            if ($suggested) {
                $data['category_id'] = $suggested['category_id'];
            }
        }

        $data['user_id'] = $user->id;

        $transaction = DB::transaction(function () use ($data, $account, $toAccount) {
            $tx = Transaction::create($data);
            $this->applyBalanceEffect($tx, $account, $toAccount, 1);
            return $tx;
        });

        return response()->json($transaction->load(['account', 'toAccount', 'category']), 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        abort_if($transaction->user_id !== auth()->id(), 403);

        return response()->json($transaction->load(['account', 'toAccount', 'category']));
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        abort_if($transaction->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'account_id'    => 'sometimes|integer|exists:accounts,id',
            'to_account_id' => 'nullable|integer|exists:accounts,id',
            'category_id'   => 'nullable|integer|exists:categories,id',
            'type'          => 'sometimes|in:income,expense,transfer,adjustment',
            'amount'        => 'sometimes|numeric|min:0.01',
            'date'          => 'sometimes|date',
            'description'   => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'is_recurring'  => 'nullable|boolean',
        ]);

        $user = auth()->user();

        DB::transaction(function () use ($transaction, $data, $user) {
            // Reverse old effect
            $oldAccount   = Account::find($transaction->account_id);
            $oldToAccount = $transaction->to_account_id ? Account::find($transaction->to_account_id) : null;
            $this->applyBalanceEffect($transaction, $oldAccount, $oldToAccount, -1);

            $transaction->update($data);
            $transaction->refresh();

            // Apply new effect
            $newAccount   = Account::find($transaction->account_id);
            $newToAccount = $transaction->to_account_id ? Account::find($transaction->to_account_id) : null;
            $this->applyBalanceEffect($transaction, $newAccount, $newToAccount, 1);
        });

        return response()->json($transaction->load(['account', 'toAccount', 'category']));
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        abort_if($transaction->user_id !== auth()->id(), 403);

        DB::transaction(function () use ($transaction) {
            $account   = Account::find($transaction->account_id);
            $toAccount = $transaction->to_account_id ? Account::find($transaction->to_account_id) : null;
            $this->applyBalanceEffect($transaction, $account, $toAccount, -1);
            $transaction->delete();
        });

        return response()->json(null, 204);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = auth()->user();

        $transactions = $user->transactions()
            ->with(['account', 'toAccount', 'category'])
            ->filter($request)
            ->latest('date')
            ->orderByDesc('id');

        $filename = 'transactions-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Category', 'Account', 'To Account', 'Description', 'Amount', 'Recurring']);

            foreach ($transactions->cursor() as $tx) {
                fputcsv($out, [
                    $tx->date->format('Y-m-d'),
                    $tx->type,
                    $tx->category?->name ?? '',
                    $tx->account->name ?? '',
                    $tx->toAccount?->name ?? '',
                    $tx->description ?? '',
                    number_format((float) $tx->amount, 2, '.', ''),
                    $tx->is_recurring ? 'Yes' : 'No',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function applyBalanceEffect(Transaction $tx, ?Account $account, ?Account $toAccount, int $direction): void
    {
        if (!$account) {
            return;
        }

        $amount = (float) $tx->amount * $direction;

        match ($tx->type) {
            'income'     => $account->increment('balance', $amount),
            'expense'    => $account->decrement('balance', $amount),
            'adjustment' => $amount >= 0
                ? $account->increment('balance', $amount)
                : $account->decrement('balance', abs($amount)),
            'transfer'   => (function () use ($account, $toAccount, $amount) {
                $account->decrement('balance', $amount);
                if ($toAccount) {
                    $toAccount->increment('balance', $amount);
                }
            })(),
        };
    }
}
