<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 50)->unique()->comment('设置项键名');
            $table->string('value', 255)->default('')->comment('设置项值');
            $table->string('label', 100)->default('')->comment('设置项名称');
            $table->timestamps();
        });

        // 插入默认费用设置
        DB::table('settings')->insert([
            [
                'key'   => 'category_fee',
                'value' => '360',
                'label' => '组别费用（元）',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key'   => 'open_weight_fee',
                'value' => '80',
                'label' => '无差别组别费用（元）',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
