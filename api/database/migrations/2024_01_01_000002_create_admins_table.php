<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('姓名');
            $table->string('email', 100)->unique()->comment('邮箱');
            $table->string('password')->comment('密码');
            $table->string('role', 20)->default('admin')->comment('角色：super_admin/admin');
            $table->string('avatar')->default('')->comment('头像');
            $table->tinyInteger('status')->default(1)->comment('状态：1正常 0禁用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
