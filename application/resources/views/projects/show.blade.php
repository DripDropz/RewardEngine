<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Project: :projectName', ['projectName' => $project->name]) }}
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
                            {{ __('View your project, api keys and manage acceptable events and accomplishments.') }}
                        </p>
                    </header>

                    <form method="post" action="{{ route('projects.update', $project) }}">
                        @csrf
                        <div class="mt-6 space-y-6">

                            <div>
                                <x-input-label for="name" :value="__('Project Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $project->name)" minlength="3" required autofocus autocomplete="name" />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="geo_blocked_countries" :value="__('GEO Blocked Countries')" />
                                <x-text-input id="geo_blocked_countries" name="geo_blocked_countries" type="text" class="mt-1 block w-full" :value="old('geo_blocked_countries', $project->geo_blocked_countries)" minlength="2" autocomplete="geo_blocked_countries" />
                                <x-input-error class="mt-2" :messages="$errors->get('geo_blocked_countries')" />
                                <p class="text-gray-600 text-sm pt-1">
                                    Enter list of countries you wish to block in comma separated 2 letter country code format. For example <strong>RU</strong> is Russian Federation.
                                    <a href="https://github.com/lukes/ISO-3166-Countries-with-Regional-Codes/blob/master/all/all.csv" class="underline" target="_blank">View all country codes</a>.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div>
                                        <x-input-label for="public_api_key" :value="__('Public API Key')" />
                                        <x-text-input id="public_api_key" type="text" class="mt-1 block w-full bg-indigo-50" :value="$project->public_api_key" readonly />
                                    </div>
                                </div>
                                <div>
                                    <div>
                                        <x-input-label for="public_api_key" :value="__('Private API Key')" />
                                        <x-revealable-password-input id="public_api_key" type="text" class="mt-1 block w-full bg-indigo-50" :value="$project->private_api_key" readonly />
                                    </div>
                                </div>
                            </div>

                            <div class="border rounded p-3 border-orange-600 bg-orange-50">
                                <label for="regenerate_api_keys" class="inline-flex items-center">
                                    <input id="regenerate_api_keys" name="regenerate_api_keys" value="yes" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ms-2 text-sm text-gray-600">{{ __('Generate new public/private api key pair') }}</span>
                                </label>
                                <x-input-error class="mt-2" :messages="$errors->get('regenerate_api_keys')" />
                                <p class="text-orange-600 text-sm pt-1">
                                    Caution: this could break any existing integration that relies on the above public/private api key pairs!
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                            </div>

                        </div>
                    </form>
                </section>
            </div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('Social Auth Login Links') }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Here are all of the the supported social login links for your frontend.') }}
                        </p>
                    </header>

                    <div class="mt-6 space-y-3">

                        @foreach(\App\Enums\AuthProviderType::values() as $authProvider)
                            @if ($authProvider !== \App\Enums\AuthProviderType::WALLET->value)
                                <div>
                                    <x-input-label :for="$authProvider" :value="__(ucfirst($authProvider))" />
                                    <div class="flex rounded-lg">
                                        <input type="text" id="{{ $authProvider }}" value="{{ route('api.v1.auth.init', ['publicApiKey' => $project->public_api_key, 'authProvider' => $authProvider]) }}/?reference=your-app-identifier-123" class="py-2 px-3 pe-11 block w-full border-gray-200 shadow-sm rounded-e-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" readonly>
                                        <x-primary-link-button class="ml-4" href="{{ route('api.v1.auth.init', ['publicApiKey' => $project->public_api_key, 'authProvider' => $authProvider]) }}/?reference=your-app-identifier-123" target="_blank">{{ __('Test') }}</x-primary-link-button>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                    </div>

                    <div class="mt-6">
                        <div class="border rounded p-3 border-indigo-600 bg-indigo-50">
                            <x-input-label for="check" value="To check the status of an initiated social authentication" />
                            <div class="flex rounded-lg">
                                <input type="text" id="check" value="{{ route('api.v1.auth.check', ['publicApiKey' => $project->public_api_key ]) }}/?reference=your-app-identifier-123" class="py-2 px-3 pe-11 block w-full border-gray-200 shadow-sm rounded-e-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" readonly>
                                <x-primary-link-button class="ml-4" href="{{ route('api.v1.auth.check', ['publicApiKey' => $project->public_api_key ]) }}/?reference=your-app-identifier-123" target="_blank">{{ __('Test') }}</x-primary-link-button>
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
                            {{ __('Define a list of acceptable event types that will be submitted via the stats api endpoint.') }}
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
                            {{ __("Define various accomplishments based possible event types and their value requirements.") }}
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
