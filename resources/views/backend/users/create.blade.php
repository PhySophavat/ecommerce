@extends('layouts.application')

@section('content')
    <div class="relative isolate">
        <div class="pointer-events-none absolute inset-0 mesh-overlay"></div>

        <main class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
            <section class="glass-panel rounded-[2rem] px-6 py-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-teal-200/80">Laravel form</p>
                        <h1 class="mt-2 font-display text-4xl text-stone-50 sm:text-5xl">Add a user without Vue.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-200/78 sm:text-base">
                            This page uses a standard Laravel controller, validation, and Blade form. After saving, the new user will appear in the frontend user list.
                        </p>
                    </div>

                    <a
                        class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-stone-100 transition hover:-translate-y-0.5 hover:bg-white/10"
                        href="{{ route('frontend.home') }}"
                    >
                        Open frontend
                    </a>
                </div>
            </section>

            @if ($errors->any())
                <section class="glass-panel mt-6 rounded-[1.5rem] border border-rose-300/30 bg-rose-500/10 px-5 py-4 text-sm text-rose-100">
                    <p class="font-medium">Please fix the following errors:</p>
                    <ul class="mt-3 space-y-2 text-rose-100/90">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="glass-panel mt-6 rounded-[2rem] px-6 py-6 lg:px-8">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-stone-300/65">Create user</p>
                    <h2 class="mt-2 font-display text-3xl text-stone-50">Save user data with Laravel.</h2>
                </div>

                <form class="mt-6 space-y-4" method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <label class="block space-y-2 text-sm">
                        <span class="text-stone-200/75">Name</span>
                        <input
                            class="w-full rounded-2xl border border-white/10 bg-black/12 px-4 py-3 text-stone-50 outline-none transition placeholder:text-stone-500 focus:border-teal-300/40"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Jamie Carter"
                            required
                        />
                    </label>

                    <label class="block space-y-2 text-sm">
                        <span class="text-stone-200/75">Email</span>
                        <input
                            class="w-full rounded-2xl border border-white/10 bg-black/12 px-4 py-3 text-stone-50 outline-none transition placeholder:text-stone-500 focus:border-teal-300/40"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="jamie@example.com"
                            required
                        />
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block space-y-2 text-sm">
                            <span class="text-stone-200/75">Password</span>
                            <input
                                class="w-full rounded-2xl border border-white/10 bg-black/12 px-4 py-3 text-stone-50 outline-none transition focus:border-teal-300/40"
                                type="password"
                                name="password"
                                required
                            />
                        </label>

                        <label class="block space-y-2 text-sm">
                            <span class="text-stone-200/75">Confirm password</span>
                            <input
                                class="w-full rounded-2xl border border-white/10 bg-black/12 px-4 py-3 text-stone-50 outline-none transition focus:border-teal-300/40"
                                type="password"
                                name="password_confirmation"
                                required
                            />
                        </label>
                    </div>

                    <button
                        class="w-full rounded-full bg-stone-50 px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-white"
                        type="submit"
                    >
                        Create user
                    </button>
                </form>
            </section>
        </main>
    </div>
@endsection
