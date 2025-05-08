<?php

namespace App\Http\Controllers\Admin;

use App\Commons\CodeMasters\Role;
use App\Console\Commands\CrawlPdfPages;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request) {}

    public function getAllUsers()
    {
        $users = User::where('is_admin', '!=', Role::ADMIN())->get();

        return response()->json([
            'users' => $users->load('user_info'),
        ]);
    }

    public function crawl(Request $request)
    {
        $url = $request->input('url');

        // $url = 'http://seibunsya.sphinx-net.jp/webcatalog/hoiku2025/html5print.html?start=' . $start . '&end=' . $end . '&bookpath=.%2F&imagepath=.%2F&tegaki=on';
        $handler = new CrawlPdfPages();
        $handler->handle($url); // Truyền URL và đường dẫn vào đây

        return response()->json(['message' => 'Đang xử lý tải PDF...']);
    }
}
