
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // NULL is allowed for the system-wide Owner account
            $table->foreignId('branch_id')
                ->nullable()
                ->after('password')
                ->constrained('branches')
                ->restrictOnDelete();

            // Employee account status
            $table->boolean('is_active')->default(true)->after('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'is_active']);
        });
    }
};
