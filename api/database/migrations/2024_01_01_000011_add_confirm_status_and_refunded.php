<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddConfirmStatusAndRefunded extends Migration
{
    public function up()
    {
        // 1. 添加确认状态列
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('confirm_status', 20)->default('pending')
                  ->after('pay_status')
                  ->comment('确认状态: pending待确认, confirmed已确认');
        });

        // 2. 扩展 pay_status 枚举，增加 refunded
        DB::statement("ALTER TABLE registrations MODIFY COLUMN pay_status ENUM('pending','paid','cancelled','refund_pending','refunded') NOT NULL DEFAULT 'pending' COMMENT '支付状态'");
    }

    public function down()
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('confirm_status');
        });

        DB::statement("ALTER TABLE registrations MODIFY COLUMN pay_status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending' COMMENT '支付状态'");
    }
}
