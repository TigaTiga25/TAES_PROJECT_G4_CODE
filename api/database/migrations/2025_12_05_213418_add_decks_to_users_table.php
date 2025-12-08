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
        Schema::table('users', function (Blueprint $table) {
            // Guarda a lista de baralhos comprados (ex: ["default", "retro", "gold"])
            $table->text('unlocked_decks')->nullable(); 
            
            // Guarda o nome da pasta do baralho atual (ex: "default")
            $table->string('current_deck')->default('default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
