<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ProjectsController extends Controller
{
    public function index(): View|Factory|Application
    {
        $myProjects = Project::query()
            ->where('user_id', auth()->id())
            ->paginate(10);

        return view('projects.index', compact('myProjects'));
    }

    public function create(): View|Factory|Application
    {
        return view('projects.create');
    }

    public function show(int $projectId): View|Factory|Application|RedirectResponse
    {
        $project = Project::query()
            ->where('user_id', auth()->id())
            ->where('id', $projectId)
            ->first();

        if (!$project) {
            return redirect()
                ->route('projects.index')
                ->with('alert', __('Project not found.'));
        }

        return view(
            'projects.show',
            compact('project'),
        );
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = new Project;
        $project->fill([
            'user_id' => auth()->id(),
            'name' => $request->validated('name'),
            'public_api_key' => Str::uuid(),
            'private_api_key' => Str::uuid(),
            'geo_blocked_countries' => $request->validated('geo_blocked_countries'),
            'session_valid_for_seconds' => $request->validated('session_valid_for_seconds'),
        ]);
        $project->save();

        return redirect()
            ->route('projects.show', $project)
            ->with('alert', __('New project (:name) successfully created.', ['name' => $request->validated('name')]));
    }

    public function update(int $projectId, StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::query()
            ->where('user_id', auth()->id())
            ->where('id', $projectId)
            ->first();

        if (!$project) {
            return redirect()
                ->route('projects.index')
                ->with('alert', __('Project not found.'));
        }

        $changes = [
            'name' => $request->validated('name'),
            'geo_blocked_countries' => $request->validated('geo_blocked_countries'),
            'session_valid_for_seconds' => $request->validated('session_valid_for_seconds'),
        ];
        if ($request->validated('regenerate_api_keys') === 'yes') {
            $changes['public_api_key'] = Str::uuid();
            $changes['private_api_key'] = Str::uuid();
        }

        $project->update($changes);

        return redirect()
            ->route('projects.show', $project)
            ->with('alert', __('Project (:name) successfully updated.', ['name' => $request->validated('name')]));
    }
}
