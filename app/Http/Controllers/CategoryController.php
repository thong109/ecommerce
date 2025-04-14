<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getCategories()
    {
        $categories = Category::where('status', 1)->get();

        $categories->load(['products' => function ($query) {
            $query->where('status', 1);
        }]);

        return response()->json($categories);
    }

    public function getAttributes(Category $category)
    {
        $attributes = $category->attributes()->with('values')->get();
        return response()->json($attributes);
    }
}
