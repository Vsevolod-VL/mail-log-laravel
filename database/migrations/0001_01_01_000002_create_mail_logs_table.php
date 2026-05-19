<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')
                ->constrained($this->groupsTable())
                ->cascadeOnDelete();
            $table->json('to');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('status', 16);
            $table->text('error_message')->nullable();
            $table->double('seconds')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['group_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('mail-log.tables.events', 'mail_logs');
    }

    private function groupsTable(): string
    {
        return (string) config('mail-log.tables.groups', 'mail_log_groups');
    }
};
