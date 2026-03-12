<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->comment('公告标题');
            $table->text('content')->comment('公告内容（小程序展示用）');
            $table->tinyInteger('status')->default(1)->comment('状态：1显示 0隐藏');
            $table->integer('sort')->default(0)->comment('排序（越大越靠前）');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
