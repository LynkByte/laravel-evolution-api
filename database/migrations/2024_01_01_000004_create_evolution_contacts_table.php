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
        Schema::create('evolution_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('instance_name')->index();
            $table->string('remote_jid')->index();
            $table->string('phone_number')->nullable();
            $table->string('push_name')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->boolean('is_business')->default(false);
            $table->boolean('is_group')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['instance_name', 'remote_jid']);
            $table->index('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolution_contacts');
    }
};
