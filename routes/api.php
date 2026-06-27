<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', 'App\Http\Controllers\Api\AuthController@login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', 'App\Http\Controllers\Api\AuthController@logout');
    Route::get('/me', 'App\Http\Controllers\Api\AuthController@me');
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('incomes', 'App\Http\Controllers\Api\IncomeController');
    Route::apiResource('expenses', 'App\Http\Controllers\Api\ExpenseController');
    Route::post('/saving-goals/{saving_goal}/deposit', 'App\Http\Controllers\Api\SavingGoalController@deposit');
    Route::apiResource('saving-goals', 'App\Http\Controllers\Api\SavingGoalController');
    Route::apiResource('debts', 'App\Http\Controllers\Api\DebtController');

    Route::get('/finance/summary', 'App\Http\Controllers\Api\FinanceController@summary');
    Route::get('/finance/projection', 'App\Http\Controllers\Api\FinanceController@projection');
    Route::get('/finance/dashboard', 'App\Http\Controllers\Api\FinanceController@dashboardSummary');

    // Categories
    Route::get('/categories', 'App\Http\Controllers\Api\CategoryController@index');
    Route::get('/categories/suggest', 'App\Http\Controllers\Api\CategoryController@suggest');

    // Accounts
    Route::apiResource('accounts', 'App\Http\Controllers\Api\AccountController');

    // Transactions (export before resource to avoid wildcard conflict)
    Route::get('/transactions/export', 'App\Http\Controllers\Api\TransactionController@export');
    Route::apiResource('transactions', 'App\Http\Controllers\Api\TransactionController');

    // Budgets
    Route::apiResource('budgets', 'App\Http\Controllers\Api\BudgetController')->except(['show']);
});
