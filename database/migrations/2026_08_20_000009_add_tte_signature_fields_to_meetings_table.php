<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // TTE Notulen
            $table->timestamp('minutes_signed_at')->nullable()->after('signer_rank');
            $table->foreignId('minutes_signed_by')->nullable()->constrained('users')->nullOnDelete()->after('minutes_signed_at');
            $table->string('minutes_signed_path')->nullable()->after('minutes_signed_by');

            // TTE Daftar Hadir
            $table->timestamp('attendance_signed_at')->nullable()->after('minutes_signed_path');
            $table->foreignId('attendance_signed_by')->nullable()->constrained('users')->nullOnDelete()->after('attendance_signed_at');
            $table->string('attendance_signed_path')->nullable()->after('attendance_signed_by');

            // TTE Dokumentasi Foto
            $table->timestamp('photos_signed_at')->nullable()->after('attendance_signed_path');
            $table->foreignId('photos_signed_by')->nullable()->constrained('users')->nullOnDelete()->after('photos_signed_at');
            $table->string('photos_signed_path')->nullable()->after('photos_signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['minutes_signed_by']);
            $table->dropForeign(['attendance_signed_by']);
            $table->dropForeign(['photos_signed_by']);

            $table->dropColumn([
                'minutes_signed_at', 'minutes_signed_by', 'minutes_signed_path',
                'attendance_signed_at', 'attendance_signed_by', 'attendance_signed_path',
                'photos_signed_at', 'photos_signed_by', 'photos_signed_path',
            ]);
        });
    }
};
