<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('session_id')->constrained('school_sessions')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->onDelete('cascade');
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'class_id'], 'fee_struct_session_class_idx');
        });

        Schema::create('fee_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->string('name');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('school_sessions')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['student_id', 'session_id', 'fee_structure_id'], 'student_fee_unique');
            $table->index(['session_id', 'class_id'], 'student_fee_session_class_idx');
        });

        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('school_sessions')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_mode'); // Cash, UPI, Cheque, Card, Bank Transfer, Online, Other
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['payment_date', 'student_id', 'class_id', 'session_id', 'payment_mode'], 'fee_pay_search_idx');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('payment_mode');
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['date', 'category', 'payment_mode'], 'expenses_search_idx');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type'); // 'income' or 'expense'
            $table->foreignId('fee_payment_id')->nullable()->constrained('fee_payments')->onDelete('cascade');
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('session_id')->nullable()->constrained('school_sessions')->onDelete('set null');
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->onDelete('set null');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('payment_mode');
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['date', 'transaction_type', 'payment_mode', 'session_id', 'class_id'], 'trx_search_idx');
        });

        Schema::create('fee_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('school_sessions')->onDelete('cascade');
            $table->string('channel'); // SMS, WhatsApp, Both
            $table->text('message_template');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('fee_reminder_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_reminder_id')->constrained('fee_reminders')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('phone_used');
            $table->decimal('due_amount', 12, 2);
            $table->string('status')->default('Sent'); // Sent, Pending, Failed
            $table->text('provider_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fee_reminder_recipients');
        Schema::dropIfExists('fee_reminders');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('student_fees');
        Schema::dropIfExists('fee_installments');
        Schema::dropIfExists('fee_structures');
    }
}
