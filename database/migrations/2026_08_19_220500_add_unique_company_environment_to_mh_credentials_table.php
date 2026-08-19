<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mh_credentials', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'environment'],
                'mh_credentials_company_environment_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('mh_credentials', function (Blueprint $table) {
            $table->dropUnique('mh_credentials_company_environment_unique');
        });
    }
};
