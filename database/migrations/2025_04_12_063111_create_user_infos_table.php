<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->string('phone')->unique()->nullable();
            $table->string('address')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->longText('avatar')->nullable();
            $table->longText('avatar_old')->nullable();
            $table->tinyInteger('avatar_status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_infos');
    }
};
