<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('agenda')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('location');
            $table->string('status')->default('scheduled'); // scheduled, ongoing, completed, cancelled
            $table->string('signer_title')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signer_nip')->nullable();
            $table->string('signer_rank')->nullable();

            // TTE Notulen
            $table->timestamp('minutes_signed_at')->nullable();
            $table->foreignId('minutes_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('minutes_signed_path')->nullable();

            // TTE Daftar Hadir / Presensi
            $table->timestamp('attendance_signed_at')->nullable();
            $table->foreignId('attendance_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attendance_signed_path')->nullable();

            // TTE Dokumentasi Foto
            $table->timestamp('photos_signed_at')->nullable();
            $table->foreignId('photos_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('photos_signed_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
