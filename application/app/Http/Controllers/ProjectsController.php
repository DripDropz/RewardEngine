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

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = new Project;
        $project->fill([
            'user_id' => auth()->id(),
            'name' => $request->validated('name'),
            'api_key' => encrypt(Str::uuid()),
        ]);
        $project->save();

        return redirect()
            ->route('projects.index')
            ->with('alert', __('New project (:name) created successfully.', ['name' => $request->validated('name')]));
    }
}
