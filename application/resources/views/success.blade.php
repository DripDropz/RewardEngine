<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'RewardEngine') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/tailwind_app.css', 'resources/js/tailwind_app.js'])
        <script>
            setTimeout(() => window.close(), 5000);
        </script>
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">
        <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
            <img id="background" class="absolute -left-20 top-0 max-w-[877px]" src="{{ asset('images/background.svg') }}" />
            <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
                <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                    <main class="mt-6">
                        <div class="rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                            <div>
                                <p class="text-center text-xl dark:text-white">
                                    {{ __('Successfully logged in via :authProvider', ['authProvider' => $authProvider]) }}
                                </p>
                            </div>
                            <div class="py-6">
                                <div class="flex justify-center items-center gap-4">
                                    <img class="w-10 h-10 rounded-full" src="{{ $avatar }}" alt="">
                                    <div class="dark:text-white">
                                        <div>{{ $name }}</div>
                                        <div class="text-sm">{{ $email }}</div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-center text-sm">
                                    {{ __('you may close this tab and return to the application') }}
                                </p>
                            </div>
                        </div>
                    </main>
                    <footer class="py-16 text-center text-sm text-black dark:text-white/70">
                        Powered by <a class="underline" href="https://github.com/DripDropz/RewardEngine" target="_blank">DripDropz.io RewardEngine</a> {{ config('app.version') }}
                    </footer>
                </div>
            </div>
        </div>
    </body>
</html>
