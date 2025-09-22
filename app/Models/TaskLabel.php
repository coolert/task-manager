<?php

namespace App\Models;

use Database\Factories\TaskLabelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskLabel extends Pivot
{
    /** @use HasFactory<TaskLabelFactory> */
    use HasFactory;

    protected $table = 'task_labels';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'task_id',
        'label_id',
    ];

    /**
     * @phpstan-return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @phpstan-return BelongsTo<Label, $this>
     */
    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }
}
