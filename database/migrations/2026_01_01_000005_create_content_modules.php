<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->string('title', 200);
            $table->string('category', 100)->default('');
            $table->string('audience', 100)->default('All Student Portals');
            $table->enum('status', ['Draft', 'Scheduled', 'Published', 'Archived'])->default('Draft');
            $table->string('priority', 50)->default('Standard Priority');
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('author', 150)->default('');
            $table->unsignedBigInteger('views')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->dateTime('scheduled_for')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('status');
            $table->index('category');
            $table->index('published_at');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('regulations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->string('reference_code', 50)->default('');
            $table->string('title', 200);
            $table->string('category', 100)->default('');
            $table->string('audience', 100)->default('All Students');
            $table->enum('status', ['Draft', 'Pending Review', 'Published', 'Archived'])->default('Draft');
            $table->string('effective_date', 100)->default('');
            $table->string('review_schedule', 150)->default('');
            $table->string('approval_body', 200)->default('');
            $table->unsignedBigInteger('views')->default(0);
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->json('sanctions')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('status');
            $table->index('category');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('orientation_modules', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->string('title', 200);
            $table->string('category', 120)->default('');
            $table->enum('status', ['Draft', 'Published', 'Archived'])->default('Draft');
            $table->string('audience', 100)->default('Incoming Freshmen');
            $table->string('version', 20)->default('v1.0');
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedTinyInteger('mandatory')->default(1);
            $table->unsignedInteger('learners')->default(0);
            $table->unsignedInteger('completions')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('objectives')->nullable();
            $table->longText('body')->nullable();
            $table->string('department', 150)->default('');
            $table->unsignedInteger('attachments_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('status');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('orientation_progress', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->enum('status', ['Not Started', 'In Progress', 'Completed'])->default('Not Started');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->dateTime('last_viewed_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->unique(['user_id', 'module_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('orientation_modules')->onDelete('cascade');
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->string('title', 200);
            $table->string('category', 100)->default('');
            $table->enum('status', ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'])->default('Scheduled');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('venue', 180)->default('');
            $table->string('mode', 50)->default('On-campus');
            $table->string('audience', 100)->default('All Students');
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('registered')->default(0);
            $table->text('description')->nullable();
            $table->string('organizer', 180)->default('');
            $table->string('verification', 200)->default('');
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('status');
            $table->index('start_at');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('lost_found_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->enum('type', ['Lost', 'Found'])->default('Found');
            $table->string('title', 200);
            $table->string('category', 100)->default('');
            $table->text('description')->nullable();
            $table->string('location', 180)->default('');
            $table->dateTime('date_occurred')->nullable();
            $table->string('reported_by', 150)->default('');
            $table->string('reporter_email', 190)->default('');
            $table->string('student_id', 30)->default('');
            $table->string('contact', 190)->default('');
            $table->enum('status', ['Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed'])->default('Open');
            $table->string('custody', 150)->default('');
            $table->string('attachment', 255)->default('');
            $table->dateTime('posted_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('status');
            $table->index('type');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('lost_found_claims', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('claimant_user_id')->nullable();
            $table->string('claimant_name', 150);
            $table->string('claimant_email', 190);
            $table->string('student_id', 30)->default('');
            $table->text('proof')->nullable();
            $table->text('message')->nullable();
            $table->string('attachment', 255)->default('');
            $table->enum('status', ['Pending', 'Verified', 'Rejected'])->default('Pending');
            $table->string('verified_by', 150)->default('');
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('created_at');
            $table->index(['item_id', 'status']);
            $table->foreign('item_id')->references('id')->on('lost_found_items')->onDelete('cascade');
            $table->foreign('claimant_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('document_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->unsignedBigInteger('student_user_id')->nullable();
            $table->string('student_name', 150);
            $table->string('student_id', 30)->default('');
            $table->string('student_email', 190);
            $table->string('program', 150)->default('');
            $table->string('year_level', 30)->default('');
            $table->string('document_type', 150);
            $table->text('purpose')->nullable();
            $table->unsignedInteger('copies')->default(1);
            $table->string('delivery_mode', 60)->default('Pick-up at OSAS');
            $table->enum('status', ['Submitted', 'Under Review', 'Ready for Pick-up', 'Released', 'Rejected', 'Cancelled'])->default('Submitted');
            $table->string('priority', 30)->default('Medium');
            $table->dateTime('requested_at');
            $table->dateTime('updated_at');
            $table->dateTime('released_at')->nullable();
            $table->string('processed_by', 150)->default('OSAS Records');
            $table->text('remarks')->nullable();
            $table->string('attachment', 255)->default('');
            $table->dateTime('created_at');
            $table->index('student_user_id');
            $table->index('status');
            $table->foreign('student_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('document_request_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->string('status', 40)->default('');
            $table->string('by_name', 150)->default('');
            $table->text('note')->nullable();
            $table->dateTime('created_at');
            $table->index(['request_id', 'created_at']);
            $table->foreign('request_id')->references('id')->on('document_requests')->onDelete('cascade');
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->unsignedBigInteger('student_user_id')->nullable();
            $table->string('student_name', 150);
            $table->string('student_email', 190);
            $table->string('student_id', 30)->default('');
            $table->string('program', 150)->default('');
            $table->string('category', 100)->default('');
            $table->string('subject', 200);
            $table->text('description')->nullable();
            $table->string('priority', 30)->default('Medium');
            $table->enum('status', ['Open', 'In Progress', 'Resolved', 'Closed'])->default('Open');
            $table->string('assigned_to', 150)->default('');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->dateTime('submitted_at');
            $table->dateTime('updated_at');
            $table->dateTime('created_at');
            $table->index('student_user_id');
            $table->index('status');
            $table->foreign('student_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('support_messages', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('author_name', 150);
            $table->string('role', 40)->default('Student');
            $table->text('body');
            $table->dateTime('created_at');
            $table->index(['ticket_id', 'created_at']);
            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
        });

        Schema::create('broadcasts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->string('subject', 200);
            $table->text('message');
            $table->string('channel', 60)->default('Portal');
            $table->enum('status', ['Draft', 'Pending', 'Scheduled', 'Sent'])->default('Draft');
            $table->string('audience', 100)->default('All Student Portals');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('delivered')->default(0);
            $table->unsignedInteger('opened')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('status');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('reports', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->string('title', 200);
            $table->string('category', 100)->default('');
            $table->string('period', 80)->default('');
            $table->string('status', 40)->default('Completed');
            $table->string('format', 20)->default('CSV');
            $table->unsignedBigInteger('records')->default(0);
            $table->dateTime('generated_at');
            $table->string('generated_by', 150)->default('');
            $table->string('scope', 200)->default('');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('category');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('tracking', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('public_id', 30)->unique();
            $table->string('title', 200);
            $table->string('type', 80)->default('');
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->string('avg_time', 20)->default('0m 00s');
            $table->decimal('engagement', 5, 2)->default(0);
            $table->dateTime('last_viewed')->nullable();
            $table->decimal('trend', 6, 2)->default(0);
            $table->string('source_id', 40)->default('');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->index('source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('broadcasts');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('document_request_events');
        Schema::dropIfExists('document_requests');
        Schema::dropIfExists('lost_found_claims');
        Schema::dropIfExists('lost_found_items');
        Schema::dropIfExists('events');
        Schema::dropIfExists('orientation_progress');
        Schema::dropIfExists('orientation_modules');
        Schema::dropIfExists('regulations');
        Schema::dropIfExists('announcements');
    }
};
