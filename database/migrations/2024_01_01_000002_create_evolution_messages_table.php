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
        Schema::create('evolution_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->index();
            $table->string('instance_name')->index();
            $table->string('remote_jid');
            $table->string('from_me')->default(false);
            $table->string('message_type')->default('text');
            $table->string('status')->default('pending');
            $table->text('content')->nullable();
            $table->json('media')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->string('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['instance_name', 'remote_jid']);
            $table->index(['instance_name', 'status']);
            $table->index('created_at');

            // Unique constraint to prevent duplicate messages
            $table->unique(['message_id', 'instance_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolution_messages');
    }
};
