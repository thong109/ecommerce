<?php

namespace App\Http\Controllers;

use App\Commons\CodeMasters\Role;
use App\Commons\CodeMasters\Status;
use App\Models\User;
use App\Models\UserChangeRequest;
use App\Models\UserInfo;
use Illuminate\Http\Request;

class UserController extends Controller
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
     * Display the specified resource.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('user_info');

        if ($user->user_info->avatar_status === Status::HIDDEN()) {
            $user->user_info->avatar = $user->user_info->avatar_old;
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'user_info' => $user->user_info,
        ]);
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
        $this->checkUser($request);

        $userUpdate = User::find($id);

        if (!$userUpdate) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user = User::findOrFail($id);

        // Cập nhật các trường cơ bản
        $user->name = $request->input('name');
        $user->save();

        // Cập nhật thông tin user_info
        $userInfo = $user->user_info;
        $userInfo->user_id = $user->id;
        $userInfo->phone = $request->input('user_info.phone');
        $userInfo->address = $request->input('user_info.address');
        $userInfo->location = $request->input('user_info.location');
        $userInfo->description = $request->input('user_info.description');

        if ($request->hasFile('user_info.avatar')) {
            // Trường hợp gửi dạng file qua FormData
            $file = $request->file('user_info.avatar');
            $path = $file->store('avatars', 'public');
            $userInfo->avatar = $path;

            if ($userInfo->avatar_status !== Status::HIDDEN()) {
                $userInfo->avatar_old = $userInfo->avatar;
                $userInfo->avatar_status = Status::HIDDEN();
            }

            $userInfo->save();

            UserChangeRequest::where('type', 'avatar')->delete();
            UserChangeRequest::create([
                'user_id' => auth()->id(),
                'type' => 'avatar',
                'data' => json_encode([
                    'avatar' => $path
                ]),
            ]);

            return response()->json([
                'message' => 'Your photo will be updated within minutes if approved by ADM',
                'user' => $user->load('user_info')
            ]);
        }

        $userInfo->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->load('user_info')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function saveBase64Image($image)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new \Exception('Invalid image type');
            }

            $image = base64_decode($image);
            if ($image === false) {
                throw new \Exception('base64_decode failed');
            }

            $filename = uniqid() . '.' . $type;
            $path = storage_path("app/public/avatars/{$filename}");
            file_put_contents($path, $image);

            return "avatars/{$filename}";
        }

        throw new \Exception('Invalid image format');
    }

    private function checkUser(Request $request)
    {
        $userCurrent = $request->user();

        if (!$userCurrent) {
            return response()->json(['message' => 'Authentication.'], 404);
        }
    }
}
