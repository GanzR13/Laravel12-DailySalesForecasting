<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sentimen_data')) {
            Schema::create('sentimen_data', function (Blueprint $table) {
                $table->id();
                $table->text('review_text');
                $table->string('label_sentimen', 20);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sentimen_data');
    }
};
