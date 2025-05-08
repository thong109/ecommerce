<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function settings()
    {
        $setting = Setting::where('key', 'site_settings')->first();
        return response()->json($setting ? $setting->value : []);
    }

    public function setSettings(Request $request)
    {
        $request->validate([
            'banners' => 'required|array',
            'banners.*.label' => 'nullable|string',
            'banners.*.title' => 'nullable|string',
            'banners.*.description' => 'nullable|string',
            'images.*' => 'nullable|image|max:5120', // validate hình ảnh
        ]);

        $data = $request->all();
        $setting = Setting::where('key', 'site_settings')->first();

        // Lấy giá trị cũ (convert từ JSON nếu là string)
        $oldValue = $setting && is_string($setting->value)
            ? json_decode($setting->value, true)
            : ($setting->value ?? []);

        $oldBanners = $oldValue['banners'] ?? [];

        $newBanners = [];

        foreach ($data['banners'] as $index => $banner) {
            $newBanner = [
                'label' => $banner['label'] ?? '',
                'title' => $banner['title'] ?? '',
                'description' => $banner['description'] ?? '',
            ];

            // Nếu có ảnh mới upload
            if ($request->hasFile("images.$index")) {
                $file = $request->file("images.$index");
                $path = $file->store('images', 'public');
                $newBanner['image'] = $path;
            } elseif (isset($oldBanners[$index]['image'])) {
                // Giữ lại ảnh cũ nếu không có ảnh mới
                $newBanner['image'] = $oldBanners[$index]['image'];
            }

            // Nếu không có ảnh nào (mới hoặc cũ) thì không thêm vào mảng banner
            if (!empty($newBanner['image'])) {
                $newBanners[] = $newBanner;
            }
        }

        // Gán lại vào data để lưu JSON
        $data['banners'] = $newBanners;

        // Lưu vào DB
        Setting::updateOrCreate(
            ['key' => 'site_settings'],
            ['value' => json_encode($data)]
        );

        return response()->json(['message' => 'Cập nhật thành công']);
    }
}
