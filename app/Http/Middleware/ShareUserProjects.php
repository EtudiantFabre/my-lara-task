<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareUserProjects
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = $request->user();
            $projects = $user->projects()
                ->select('id', 'title', 'description', 'status', 'created_at', 'updated_at')
                ->withCount('tasks')
                ->latest()
                ->get()
                ->toArray();

            // Partager les projets sur toutes les pages
            Inertia::share([
                'auth' => array_merge(
                    $request->session()->get('auth', []),
                    [
                        'user' => array_merge(
                            $user->only('id', 'name', 'email', 'email_verified_at'),
                            ['projects' => $projects]
                        )
                    ]
                )
            ]);
        }
        
        return $next($request);
    }
}
