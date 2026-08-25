<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 10);              // mail | sms
            $table->string('type', 40);                 // reminder, booked_confirmed, …
            $table->string('recipient');                // an address, or a number as the gateway sees it
            $table->string('locale', 10);
            $table->string('subject')->nullable();      // mail only, filled in when it is sent
            // SMS only, and the whole of it: this is the record of what was
            // actually said, and it is 160 characters at worst.
            $table->text('body')->nullable();
            $table->nullableMorphs('related');          // the appointment, the document
            $table->string('status', 10)->default('queued');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // The three questions this table is asked: what happened to this
            // patient, what is stuck, and what went out today.
            $table->index('recipient');
            $table->index(['status', 'created_at']);
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
