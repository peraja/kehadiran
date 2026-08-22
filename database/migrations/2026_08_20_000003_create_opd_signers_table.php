<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opds')->cascadeOnDelete();
            $table->string('bidang_name')->nullable();
            $table->string('title')->nullable();
            $table->string('name');
            $table->string('nip')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('rank')->nullable();
            $table->string('eselon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_signers');
    }
};
