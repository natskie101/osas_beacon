<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orientation_quizzes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->unsignedBigInteger('module_id')->unique();
            $table->string('title', 200)->default('');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('time_limit_minutes')->default(20);
            $table->enum('status', ['Draft', 'Published'])->default('Draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->foreign('module_id')->references('id')->on('orientation_modules')->onDelete('cascade');
        });

        Schema::create('orientation_quiz_questions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->unsignedBigInteger('quiz_id');
            $table->text('prompt');
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('quiz_id');
            $table->foreign('quiz_id')->references('id')->on('orientation_quizzes')->onDelete('cascade');
        });

        Schema::create('orientation_quiz_options', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_id');
            $table->string('label', 500);
            $table->unsignedTinyInteger('is_correct')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->dateTime('created_at');
            $table->index('question_id');
            $table->foreign('question_id')->references('id')->on('orientation_quiz_questions')->onDelete('cascade');
        });

        Schema::create('orientation_quiz_attempts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('total_points')->default(0);
            $table->decimal('percent', 5, 2)->default(0);
            $table->unsignedTinyInteger('passed')->default(0);
            $table->enum('status', ['In Progress', 'Submitted', 'Expired'])->default('In Progress');
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('quiz_id');
            $table->index('user_id');
            $table->foreign('quiz_id')->references('id')->on('orientation_quizzes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('orientation_quiz_answers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('attempt_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('option_id')->nullable();
            $table->unsignedTinyInteger('is_correct')->default(0);
            $table->dateTime('created_at');
            $table->index('attempt_id');
            $table->foreign('attempt_id')->references('id')->on('orientation_quiz_attempts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orientation_quiz_answers');
        Schema::dropIfExists('orientation_quiz_attempts');
        Schema::dropIfExists('orientation_quiz_options');
        Schema::dropIfExists('orientation_quiz_questions');
        Schema::dropIfExists('orientation_quizzes');
    }
};
