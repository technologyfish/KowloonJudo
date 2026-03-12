<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('openid', 64)->unique()->comment('微信 openid');
            $table->string('nickname', 50)->default('')->comment('昵称');
            $table->text('avatar')->nullable()->comment('头像');
            $table->string('phone', 20)->default('')->comment('手机号');
            $table->tinyInteger('status')->default(1)->comment('状态：1正常 0禁用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
