<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Database\Factories\ProjectMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    /** @use HasFactory<ProjectMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'roles',
    ];

    protected $casts = [
        'role' => ProjectRole::class,
    ];

    /**
     * @phpstan-return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @phpstan-return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
