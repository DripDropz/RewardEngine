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
                            {{ __("View your project, api keys and manage acceptable events and accomplishments.") }}
                        </p>
                    </header>

                    <div class="mt-6 space-y-6">

                        <div>
                            <x-input-label for="name" :value="__('Project Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$project->name" readonly />
                        </div>

                        <div>
                            <x-input-label for="geo_blocked_countries" :value="__('GEO Blocked Countries')" />
                            <x-text-input id="geo_blocked_countries" name="geo_blocked_countries" type="text" class="mt-1 block w-full" :value="$project->geo_blocked_countries" readonly />
                            <p class="text-gray-600 text-sm pt-1">
                                List of countries you wish to block in comma separated 2 letter country code format. For example <strong>RU</strong> is Russian Federation.
                                <a href="https://github.com/lukes/ISO-3166-Countries-with-Regional-Codes/blob/master/all/all.csv" class="underline" target="_blank">View all country codes</a>.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div>
                                    <x-input-label for="public_api_key" :value="__('Public API Key')" />
                                    <x-revealable-password-input id="public_api_key" name="public_api_key" type="text" class="mt-1 block w-full" :value="decrypt($project->public_api_key)" readonly />
                                    <p class="text-gray-600 text-sm pt-1">
                                        This is used when communicating from a backend (e.g. wallet/social authentication).
                                    </p>
                                </div>
                            </div>
                            <div>
                                <div>
                                    <x-input-label for="public_api_key" :value="__('Private API Key')" />
                                    <x-revealable-password-input id="public_api_key" name="private_api_key" type="text" class="mt-1 block w-full" :value="decrypt($project->private_api_key)" readonly />
                                    <p class="text-gray-600 text-sm pt-1">
                                        This is used when communicating from a backend (e.g. authentication check).
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>
            </div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('Event Types') }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ __("Define a list of acceptable event types that will be submitted via the stats api endpoint.") }}
                        </p>
                    </header>

                    <div class="mt-6 space-y-6">

                        Coming Soon

                    </div>
                </section>
            </div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('Accomplishments') }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ __("Define various accomplishments based possible event types and their values.") }}
                        </p>
                    </header>

                    <div class="mt-6 space-y-6">

                        Coming Soon

                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
