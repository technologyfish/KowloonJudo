<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_rules', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('规则标题');
            $table->text('summary')->nullable()->comment('简介');
            $table->longText('content')->comment('富文本内容');
            $table->date('rule_date')->comment('规则日期');
            $table->tinyInteger('status')->default(1)->comment('状态：1启用 0禁用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_rules');
    }
};
