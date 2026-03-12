<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpenWeightToRegistrations extends Migration
{
    public function up()
    {
        Schema::table('registrations', function (Blueprint $table) {
            // 无差组别标记（仅成人可用）
            $table->boolean('gi_open')->default(false)->after('weight_gi')->comment('是否加报道服无差组别');
            $table->boolean('nogi_open')->default(false)->after('weight_nogi')->comment('是否加报无道服无差组别');

            // 体重改为可空（至少选一种）
            $table->string('weight_gi', 50)->nullable()->change();
            $table->string('weight_nogi', 50)->nullable()->change();

            // 套餐字段改为可空（新逻辑不再使用固定套餐）
            $table->string('package_key')->nullable()->change();
            $table->string('package_label')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['gi_open', 'nogi_open']);
        });
    }
}
