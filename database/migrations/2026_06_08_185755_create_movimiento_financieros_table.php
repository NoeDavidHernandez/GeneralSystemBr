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
        Schema::create('movimiento_financieros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('concepto', 255); // Ej: Drenaje Linfático, Compra de Insumos
            $table->decimal('monto', 10, 2);
            $table->string('metodo_pago', 50)->nullable(); // Ej: Efectivo, Transferencia, Tarjeta, Zelle
            $table->string('persona', 255)->nullable(); // Cliente o Proveedor
            
            $table->dateTime('fecha');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_financieros');
    }
};
