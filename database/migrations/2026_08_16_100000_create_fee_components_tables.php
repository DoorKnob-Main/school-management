<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeeComponentsTables extends Migration
{
    public function up()
    {
        // Master list of fee component types (Tuition, GST, Transport, etc.) —
        // admin-configurable per school, mirrors the LeaveType pattern.
        Schema::create('fee_component_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('calc_type')->default('fixed'); // fixed | percentage (of the fixed subtotal)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Breakup of a fee structure's total into components. Kept separate
        // from fee_installments (which is the payment-schedule split, e.g.
        // Term 1 / Term 2) — a structure can have both a component breakup
        // and an installment schedule at the same time.
        Schema::create('fee_structure_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->foreignId('fee_component_type_id')->constrained('fee_component_types')->onDelete('cascade');
            $table->decimal('amount', 12, 2)->default(0.00); // fixed value, or percentage value when calc_type = percentage
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_structure_components');
        Schema::dropIfExists('fee_component_types');
    }
}
