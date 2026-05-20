<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white">
        <header class="px-6 lg:px-10 [grid-area:header] max-w-6xl mx-auto w-full">
            <nav class="flex flex-wrap items-center gap-x-4 py-6 lg:py-10 gap-y-2 border-b border-zinc-950/5 dark:border-white/10">
                <div><span class="text-base/6 sm:text-sm/6 text-zinc-500">mayfly <sup>2026-05-18</sup> (<a href="/" class="hover:underline text-blue-600 visited:text-purple-600" wire:navigate>Oliver's Team</a>)</span></div>
                <div class="flex gap-x-3">
                    <a href="{{ route('entries.index') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600" wire:navigate>entries</a>
                    <a href="{{ route('archived-entries') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600" wire:navigate>archive</a>
                    <a href="{{ route('entries.create') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600" wire:navigate>new entry</a>
                    <a href="{{ route('teams.show') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600" wire:navigate>team</a>
                    <a href="{{ route('account.show') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600" wire:navigate>account</a>
                </div>
                <div aria-hidden="true" class="-ml-4 flex-1"></div>
                <div>
                    <span class="text-base/6 sm:text-sm/6">logged in as oli@fastmail.com <span class="text-zinc-500">[</span><button class="text-blue-600 active:bg-yellow-100">logout</button><span class="text-zinc-500">]</span></span>
                </div>
            </nav>
        </header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group position="bottom center">
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
