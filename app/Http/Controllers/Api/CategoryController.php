<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategorySuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Category::orderBy('name')->get());
    }

    public function suggest(Request $request): JsonResponse
    {
        $request->validate(['description' => 'required|string|max:255']);

        $result = CategorySuggester::suggest($request->description);

        return response()->json($result);
    }
}
