<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage Projects') }}
            </h2>
            <x-primary-link-button href="{{ route('projects.create') }}">
                {{ __('Create') }}
            </x-primary-link-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <div class="overflow-hidden w-full overflow-x-auto rounded-md border border-neutral-300">
                        <table class="w-full text-left text-sm text-neutral-600">
                            <thead class="border-b border-neutral-300 bg-neutral-50 text-sm text-neutral-900">
                            <tr>
                                <th scope="col" class="p-4">Project Name</th>
                                <th scope="col" class="p-4">Created</th>
                                <th scope="col" class="p-4">Action</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-300">
                            @if ($myProjects->count())
                                @foreach($myProjects as $myProject)
                                    <tr class="even:bg-black/5">
                                        <td class="p-4">{{ $myProject->name }}</td>
                                        <td class="p-4">{{ $myProject->created_at->diffForHumans() }}</td>
                                        <td class="p-4">
                                            <x-primary-link-button href="#">Manage</x-primary-link-button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="even:bg-black/5">
                                    <td colspan="3" class="p-4 text-center">You have not created any projects yet</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>

                    @if ($myProjects->hasPages())
                        <div class="pt-6">
                            {{ $myProjects->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
