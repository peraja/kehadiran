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
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('signer_title')->nullable()->after('status');
            $table->string('signer_name')->nullable()->after('signer_title');
            $table->string('signer_nip')->nullable()->after('signer_name');
            $table->string('signer_rank')->nullable()->after('signer_nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['signer_title', 'signer_name', 'signer_nip', 'signer_rank']);
        });
    }
};
