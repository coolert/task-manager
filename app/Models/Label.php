<?php

namespace App\Models;

use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Label extends Model
{
    /** @use HasFactory<LabelFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'color',
    ];

    /**
     * @phpstan-return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @phpstan-return HasMany<TaskLabel, $this>
     */
    public function taskLabels(): HasMany
    {
        return $this->hasMany(TaskLabel::class);
    }

    /**
     * @phpstan-return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_labels')->withTimestamps();
    }

    /**
     * @param  Builder<Label>  $query
     *
     * @return Builder<Label>
     */
    public function scopeOfProject(Builder $query, int $project_id): Builder
    {
        return $query->where('id', $project_id);
    }
}
