<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function getBrands()
    {
        $brands = Brand::where('status', 1)->get();

        $brands->load(['products' => function ($query) {
            $query->where('status', 1);
        }]);

        return response()->json($brands);
    }
}
