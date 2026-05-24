<x-layouts::auth title="Welcome">
    <div class="grid lg:grid-cols-[1fr_320px] gap-x-12 lg:gap-x-16 gap-y-8">
        <div>
            <flux:heading level="1">capture. reflect. let go.</flux:heading>

            <div class="mt-4 space-y-4 max-w-prose">
                <p>mayfly is a rapid logging tool inspired by the <a href="https://bulletjournal.com" class="underline decoration-current/50 hover:decoration-current text-blue-700 visited:text-purple-700 dark:text-blue-400 dark:visited:text-purple-400">bullet journal</a> technique. It's for capturing tasks and notes when you don't have your notebook with you, or when it's easier to just use your phone — walking, commuting, whenever something crosses your mind. Write it down, free your mind, move on.</p>

                <p>You log entries called bullets. A bullet can be a task (something you intend to do) or a note (something you want to capture). Type it, pick the type, and move on. No categories, no tags, no projects. Just log it.</p>

                <p>When you have time, review your bullets and act on them: complete a task, migrate it to your notebook or another system, drop it if it's no longer relevant, or remove it entirely. The reflect view shows only active bullets that haven't been resolved.</p>

                <p>Every bullet expires 72 hours after creation. After that it's permanently deleted — no archive, no trash, no recovery. This is by design. Most things that feel important in the moment lose relevance within a few days. If you haven't acted on something in three days — completed it, migrated it, or even just reflected on it — it probably wasn't worth keeping. The 72-hour window is enough time to decide what matters.</p>
            </div>
        </div>

        @guest
            <div>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <flux:field class="max-w-sm">
                        <flux:label class="lowercase">Email address</flux:label>
                        <flux:input
                            name="email"
                            :value="old('email')"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                        />
                        <flux:error name="email" />
                    </flux:field>
                    <flux:field class="mt-2 max-w-sm">
                        <flux:label class="lowercase">Password</flux:label>
                        <flux:input
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                        />
                        <flux:error name="password" />
                    </flux:field>
                    <div class="mt-4 flex items-center gap-x-4 lowercase">
                        <flux:checkbox name="remember" label="Remember me" :checked="old('remember')" />
                    </div>
                    <div class="mt-4">
                        <flux:button variant="primary" color="lime" type="submit" data-test="login-button" class="lowercase">
                            Sign in
                        </flux:button>
                    </div>
                </form>

                <div class="mt-2">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="hover:underline text-blue-700 visited:text-purple-700 dark:text-blue-400 dark:visited:text-purple-400 lowercase" wire:navigate>Reset password</a>
                    @endif
                </div>
            </div>
        @endguest
    </div>
</x-layouts::auth>
