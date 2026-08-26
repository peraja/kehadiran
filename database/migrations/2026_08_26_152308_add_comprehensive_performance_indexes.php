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
        Schema::table('users', function (Blueprint $table) {
            $table->index('unit_name');
        });

        Schema::table('opds', function (Blueprint $table) {
            $table->index('name');
            $table->index('is_active');
            $table->index('leader_nip');
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->index(['meeting_id', 'user_id']);
            $table->index('check_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['unit_name']);
        });

        Schema::table('opds', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['leader_nip']);
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropIndex(['meeting_id', 'user_id']);
            $table->dropIndex(['check_in']);
        });
    }
};
