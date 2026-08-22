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
        Schema::table('opds', function (Blueprint $table) {
            $table->string('leader_name')->nullable()->after('email');
            $table->string('leader_nip')->nullable()->after('leader_name');
            $table->string('leader_title')->nullable()->after('leader_nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opds', function (Blueprint $table) {
            $table->dropColumn(['leader_name', 'leader_nip', 'leader_title']);
        });
    }
};
