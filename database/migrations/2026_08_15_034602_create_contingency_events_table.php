<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Environment;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contingency_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained();
            $table->string('generation_code')->notNullable()->unique();
            $table->string('environment')->default(Environment::SANDBOX->value);
            $table->string('contingency_type')->notNullable();
            $table->text('reason')->notNullable();
            $table->datetime('start_date_at')->notNullable();
            $table->datetime('end_date_at')->nullable();
            $table->json('original_json')->notNullable();
            $table->json('signed_json')->notNullable();
            $table->text('recieved_seal');
            $table->string('status');
            $table->string('observations');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contingency_events');
    }
};
