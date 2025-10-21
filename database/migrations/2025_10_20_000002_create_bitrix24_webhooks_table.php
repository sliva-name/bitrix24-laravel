<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Выполнить миграцию.
     */
    public function up(): void
    {
        Schema::create('bitrix24_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event')->index();
            $table->string('handler');
            $table->string('domain')->index();
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Откатить миграцию.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitrix24_webhooks');
    }
};
