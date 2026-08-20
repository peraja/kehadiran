<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropColumn(['summary', 'discussion', 'decisions']);
            $table->longText('content')->nullable()->after('meeting_id');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropColumn('content');
            $table->longText('summary')->nullable();
            $table->longText('discussion')->nullable();
            $table->longText('decisions')->nullable();
        });
    }
};
