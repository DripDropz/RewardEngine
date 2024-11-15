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
            'public_api_key' => encrypt(Str::uuid()),
            'private_api_key' => encrypt(Str::uuid()),
            'geo_blocked_countries' => $request->validated('geo_blocked_countries'),
        ]);
        $project->save();

        return redirect()
            ->route('projects.index')
            ->with('alert', __('New project (:name) successfully created.', ['name' => $request->validated('name')]));
    }
}
