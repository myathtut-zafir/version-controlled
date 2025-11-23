<?php

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
        Schema::create('object_stores', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255);
            $table->jsonb('value');
            $table->unsignedBigInteger('created_at_timestamp')->comment('UNIX timestamp (UTC)');

            $table->index(['key', 'created_at_timestamp'], 'idx_key_created_at_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_stores');
    }
};
