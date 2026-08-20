<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Guest Fields
            $table->string('guest_name')->nullable();
            $table->string('guest_agency')->nullable();
            
            // Signature
            $table->text('signature')->nullable();
            
            $table->timestamp('check_in')->nullable();
            $table->string('method')->default('qr'); // qr, manual, system
            $table->string('device_info')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
    }
};
