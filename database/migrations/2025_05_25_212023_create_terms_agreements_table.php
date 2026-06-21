<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('terms_agreements', function (Blueprint $table) {
            $table->id();

            // المستخدم
            $table->unsignedBigInteger('user_id')->unique();

            // معلومات الموافقة
            $table->string('full_name'); // الاسم الكامل كما أدخله المستخدم وقت الموافقة
            $table->string('phone')->nullable(); // رقم الهاتف (لو مطلوب)
            $table->ipAddress('ip_address'); // الـ IP وقت الضغط على "أوافق"
            $table->string('user_agent')->nullable(); // نوع الجهاز والمتصفح

            // نسخة الشروط عند التوقيع (حتى لو تم تعديلها لاحقًا)
            $table->longText('agreement_snapshot')->nullable(); // HTML أو نص الشروط وقت الموافقة

            // توقيت الموافقة
            $table->timestamp('agreed_at');

            // صلاحية لاحقة للتوقيع الجديد إذا تغيرت الشروط
            $table->boolean('is_latest_version')->default(true);
            $table->string('version')->default('v1.0');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_agreements');
    }
};
