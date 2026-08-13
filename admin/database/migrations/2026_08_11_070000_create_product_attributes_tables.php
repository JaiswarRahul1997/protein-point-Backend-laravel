<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('frontend_input')->default('select'); // select
            $table->boolean('is_required')->default(true);
            $table->boolean('is_visible_on_frontend')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('enabled');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('product_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('enabled');
            $table->timestamps();

            $table->unique(['attribute_id', 'value']);
            $table->index(['attribute_id', 'status', 'sort_order']);
        });

        Schema::create('product_attribute_option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('attribute_option_id')->constrained('product_attribute_options')->cascadeOnDelete();
            $table->unique(['product_id', 'attribute_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_option');
        Schema::dropIfExists('product_attribute_options');
        Schema::dropIfExists('product_attributes');
    }
};
