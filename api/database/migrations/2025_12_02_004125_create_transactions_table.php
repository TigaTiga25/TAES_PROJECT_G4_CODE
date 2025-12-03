<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Liga a transação ao utilizador
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->char('type', 1); // 'P' (Pagamento) ou 'I' (Income/Ganho)
            $table->integer('value'); 
            $table->string('description')->nullable();
            $table->dateTime('date'); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
