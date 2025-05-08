<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Setting;
use App\Models\UserInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'is_admin' => 1
        ]);

        UserInfo::create([
            'user_id' => 1,
        ]);

        Setting::create([
            'key' => 'site_settings',
            'value' => '"{\"notify\":\"Gi\\u1ea3m gi\\u00e1 20%\",\"theme_color\":\"#000000\",\"banners\":[{\"label\":\"Summer\",\"title\":\"\\u00c1o m\\u00f9a h\\u00e8\",\"description\":\"\\u00c1o m\\u00f9a h\\u00e8\",\"image\":\"images\\\/vqehoQNDNaPoMw3PFEW7rSuI53giAJINzznBXsq3.jpg\"},{\"label\":\"Summer\",\"title\":\"\\u00c1o m\\u00f9a h\\u00e8\",\"description\":\"\\u00c1o m\\u00f9a h\\u00e8\",\"image\":\"images\\\/aZGI8X85XybAeiIDFS0qyhs5QtvVBlVg4p0m2bOK.jpg\"}],\"images\":[{},{}]}"'
        ]);
    }
}
