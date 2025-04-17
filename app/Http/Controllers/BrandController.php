<?php

namespace App\Http\Controllers;

use App\Commons\CodeMasters\Status;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function getBrands()
    {
        $brands = Brand::where('status', Status::SHOW())->get();

        $brands->load(['products' => function ($query) {
            $query->where('status', Status::SHOW());
        }]);

        return response()->json($brands);
    }
}
