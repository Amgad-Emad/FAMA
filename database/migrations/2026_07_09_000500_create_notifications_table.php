<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's polymorphic notifications table. Fama's three Notifiable entities
 * (talent / brand / admin user) share it; the mobile API surfaces deal-turn and
 * new-message notifications from here (see App\Notifications\*). Kept as the
 * standard shape so `DatabaseNotification` works out of the box.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
