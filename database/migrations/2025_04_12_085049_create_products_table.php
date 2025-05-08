<?php

use App\Models\Brand;
use App\Models\Category;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(Category::class);
            $table->foreignIdFor(Brand::class);
            $table->string('short_desc');
            $table->text('description');
            $table->string('cost');
            $table->integer('price');
            $table->string('image');
            $table->integer('rating')->default(0);
            $table->string('tag');
            $table->integer('quantity');
            $table->tinyInteger('status')->default(1);
            $table->string('sold')->default(0); // Thêm cột deleted_at
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
