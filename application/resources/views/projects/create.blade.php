<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Projects') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('Project Information') }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ __("Create a new project, a public & private api key pair will be automatically generated.") }}
                        </p>
                    </header>

                    <form method="post" action="{{ route('projects.store') }}" class="mt-6 space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Project Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" minlength="3" required autofocus autocomplete="name" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="geo_blocked_countries" :value="__('GEO Blocked Countries')" />
                            <x-text-input id="geo_blocked_countries" name="geo_blocked_countries" type="text" class="mt-1 block w-full" :value="old('geo_blocked_countries')" minlength="2" autocomplete="geo_blocked_countries" />
                            <x-input-error class="mt-2" :messages="$errors->get('geo_blocked_countries')" />
                            <p class="text-gray-600 text-sm pt-1">
                                Enter list of countries you wish to block in comma separated 2 letter country code format. For example <strong>RU</strong> is Russian Federation.
                                <a href="https://github.com/lukes/ISO-3166-Countries-with-Regional-Codes/blob/master/all/all.csv" class="underline" target="_blank">View all country codes</a>.
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
