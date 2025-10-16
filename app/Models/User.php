<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * @return array<string,mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * @phpstan-return HasMany<Project, $this>
     */
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * @phpstan-return HasMany<ProjectMember, $this>
     */
    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * @phpstan-return HasMany<Task, $this>
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    /**
     * @phpstan-return HasMany<Task, $this>
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'creator_id');
    }

    /**
     * @phpstan-return HasMany<TaskComment, $this>
     */
    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'user_id');
    }
}
