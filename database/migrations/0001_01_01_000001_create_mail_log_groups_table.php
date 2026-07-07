<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table): void {
            $table->id();
            $table->char('fingerprint', 64)->unique();
            $table->string('subject')->nullable();
            $table->string('from')->nullable();
            $table->string('mailable_class')->nullable();
            $table->string('notification_class')->nullable();
            $table->nullableMorphs('model');
            $table->string('mailer')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('latest_status', 16)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('mail-log.tables.groups', 'mail_log_groups');
    }
};
