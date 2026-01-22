<?php

declare(strict_types=1);

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
        Schema::create('evolution_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('instance_name')->index();
            $table->string('event');
            $table->json('payload');
            $table->string('status')->default('received');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('event');
            $table->index('status');
            $table->index('created_at');
            $table->index(['instance_name', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolution_webhook_logs');
    }
};
