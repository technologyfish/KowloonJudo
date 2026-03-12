<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('age_group', 50)->comment('年龄组别')->change();
            $table->string('weight_gi', 50)->default('')->comment('体重组别（道服 GI）')->change();
            $table->string('weight_nogi', 50)->default('')->comment('体重组别（无道服 NO-GI）')->change();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('age_group', 30)->comment('年龄组别')->change();
            $table->string('weight_gi', 20)->default('')->comment('体重（道服）')->change();
            $table->string('weight_nogi', 20)->default('')->comment('体重（无道服）')->change();
        });
    }
};
