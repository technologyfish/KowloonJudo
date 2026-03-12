<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户 ID');
            $table->string('name_pinyin', 100)->default('')->comment('姓名（拼音）');
            $table->string('name_cn', 50)->default('')->comment('姓名（汉字）');
            $table->string('nationality', 50)->comment('国籍');
            $table->tinyInteger('gender')->comment('性别：1男 2女');
            $table->string('id_card', 30)->comment('身份证号码');
            $table->string('age_group', 30)->comment('年龄组别');
            $table->string('belt_color', 20)->comment('带色');
            $table->string('weight_gi', 20)->default('')->comment('体重（道服）');
            $table->string('weight_nogi', 20)->default('')->comment('体重（无道服）');
            $table->string('team', 100)->comment('战队');
            $table->string('phone', 20)->comment('手机号');
            $table->string('email', 100)->comment('邮箱');
            // 支付相关
            $table->string('package_key', 30)->default('')->comment('套餐 key');
            $table->string('package_label', 80)->default('')->comment('套餐名称');
            $table->decimal('amount', 8, 2)->default(0)->comment('应付金额');
            $table->enum('pay_status', ['pending', 'paid', 'cancelled'])->default('pending')->comment('支付状态');
            $table->string('wx_prepay_id')->default('')->comment('微信预支付 ID');
            $table->string('wx_transaction_id')->default('')->comment('微信交易流水号');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'pay_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
