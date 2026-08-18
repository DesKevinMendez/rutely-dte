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
        Schema::create('dte_invalidations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained();
            $table->foreignUuid('dte_id')->constrained();
            $table->string('generation_code')->notNullable()->unique();
            $table->string('invalidation_type')->notNullable();
            $table->text('reason')->notNullable();
            $table->string('name_person_in_charge')->notNullable();
            $table->string('doc_type_person_in_charge')->notNullable();
            $table->string('doc_number_person_in_charge')->notNullable();
            $table->string('name_request')->notNullable();
            $table->string('doc_type_request')->notNullable();
            $table->string('doc_number_request')->notNullable();
            $table->json('original_json')->notNullable();
            $table->text('signed_json')->nullable();
            $table->text('received_seal')->nullable();
            $table->string('status');
            $table->string('observations')->nullable();

            $table->string('environment')->default(Environment::SANDBOX->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_Invalidations');
    }
};
