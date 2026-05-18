<?php

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
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
        // Kosongkan receiver_id jika ini adalah pesan grup
        $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('cascade');
        // Kosongkan group_id jika ini adalah private chat 1-on-1
        $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('cascade');
        $table->text('message');
        $table->boolean('is_read')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
