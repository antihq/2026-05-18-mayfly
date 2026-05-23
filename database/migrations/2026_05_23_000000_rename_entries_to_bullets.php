<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('entries', 'bullets');

        Schema::table('bullets', function ($table) {
            $table->renameColumn('content', 'body');
        });
    }

    public function down(): void
    {
        Schema::table('bullets', function ($table) {
            $table->renameColumn('body', 'content');
        });

        Schema::rename('bullets', 'entries');
    }
};
