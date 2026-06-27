<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = auth()->user()->accounts()->orderByDesc('is_default')->orderBy('name')->get();

        return response()->json($accounts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'type'       => 'required|in:cash,bank,credit_card,debit_card,digital_wallet,other',
            'currency'   => 'nullable|string|size:3',
            'balance'    => 'nullable|numeric',
            'color'      => 'nullable|string|max:7',
            'icon'       => 'nullable|string|max:50',
            'is_default' => 'nullable|boolean',
            'notes'      => 'nullable|string',
        ]);

        $data['user_id'] = auth()->id();
        $data['currency'] = $data['currency'] ?? 'USD';

        if (!empty($data['is_default'])) {
            auth()->user()->accounts()->update(['is_default' => false]);
        }

        $account = Account::create($data);

        return response()->json($account, 201);
    }

    public function show(Account $account): JsonResponse
    {
        abort_if($account->user_id !== auth()->id(), 403);

        return response()->json($account);
    }

    public function update(Request $request, Account $account): JsonResponse
    {
        abort_if($account->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'type'       => 'sometimes|in:cash,bank,credit_card,debit_card,digital_wallet,other',
            'currency'   => 'sometimes|string|size:3',
            'balance'    => 'sometimes|numeric',
            'color'      => 'nullable|string|max:7',
            'icon'       => 'nullable|string|max:50',
            'is_default' => 'sometimes|boolean',
            'notes'      => 'nullable|string',
        ]);

        if (!empty($data['is_default'])) {
            auth()->user()->accounts()->where('id', '!=', $account->id)->update(['is_default' => false]);
        }

        $account->update($data);

        return response()->json($account);
    }

    public function destroy(Account $account): JsonResponse
    {
        abort_if($account->user_id !== auth()->id(), 403);

        if ($account->transactions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account with transactions. Remove all transactions first.',
            ], 422);
        }

        $account->delete();

        return response()->json(null, 204);
    }
}
