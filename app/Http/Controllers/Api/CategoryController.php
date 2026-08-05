<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Household;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index(Household $household): AnonymousResourceCollection
    {
        Gate::authorize('view', $household);

        return CategoryResource::collection(
            $household->categories()
                ->orderBy('type')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(StoreCategoryRequest $request, Household $household): CategoryResource
    {
        Gate::authorize('manage', $household);

        $category = Category::create([
            'household_id' => $household->id,
            'parent_id' => $request->integer('parent_id') ?: null,
            'name' => $request->string('name')->toString(),
            'type' => $request->string('type')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return new CategoryResource($category);
    }
}
