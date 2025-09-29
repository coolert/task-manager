<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectMember
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user    = $request->user();
        $project = $request->route('project');

        if (! $project instanceof Project) {
            abort(404);
        }

        $isMember = $project->projectMembers()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember && $project->owner_id !== $user->id) {
            abort(403);
        }

        return $next($request);
    }
}
