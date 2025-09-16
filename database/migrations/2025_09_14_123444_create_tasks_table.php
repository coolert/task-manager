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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('creator_id')->constrained('users');
            $table->string('title', 150);
            $table->mediumText('description')->nullable();
            $table->enum('status', ['todo', 'doing', 'done'])->default('todo');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->foreignId('assignee_id')->nullable()->constrained('users');
            $table->dateTime('due_date')->nullable();
            $table->unsignedBigInteger('order_no')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('project_id', 'idx_tasks_project');
            $table->index(['project_id', 'status', 'order_no'], 'idx_tasks_project_status');
            $table->index(['assignee_id', 'status', 'due_date'], 'idx_tasks_assignee');
            $table->index(['project_id', 'due_date'], 'idx_tasks_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
