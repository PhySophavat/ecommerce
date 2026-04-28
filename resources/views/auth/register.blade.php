@extends('layouts.application')

@section('content')
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(88,101,242,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(245,158,11,0.14),_transparent_20%)]"></div>

        <div class="admin-panel relative w-full max-w-4xl overflow-hidden rounded-[36px] px-4 py-4 sm:px-5 sm:py-5">
            <div class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
                <section class="admin-muted-panel rounded-[30px] px-6 py-8 sm:px-8">
                    <p class="chatgpt-kicker text-xs uppercase text-indigo-500">Register</p>
                    <h1 class="chatgpt-title mt-4 text-4xl text-slate-900">Create a storefront account.</h1>
                    <p class="chatgpt-copy mt-4 text-sm sm:text-base">
                        Admin accounts are seeded separately. This form only creates customer and merchant users.
                    </p>

                    <div class="mt-8 space-y-3 text-sm text-slate-600">
                        <p class="admin-card rounded-2xl px-4 py-3">Customer: browse and place orders.</p>
                        <p class="admin-card rounded-2xl px-4 py-3">Merchant: manage your own products and status.</p>
                    </div>
                </section>

                <section class="admin-card rounded-[30px] px-6 py-7 sm:px-8">
                    <form method="POST" action="{{ route('auth.register.submit') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="name" class="chatgpt-label block text-sm">Name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                autocomplete="name"
                                class="mt-2 block w-full rounded-2xl border px-4 py-3 text-sm text-slate-900"
                                required
                            >
                            @error('name')
                                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="chatgpt-label block text-sm">Email</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                class="mt-2 block w-full rounded-2xl border px-4 py-3 text-sm text-slate-900"
                                required
                            >
                            @error('email')
                                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="role" class="chatgpt-label block text-sm">Role</label>
                            <select
                                id="role"
                                name="role"
                                class="mt-2 block w-full rounded-2xl border px-4 py-3 text-sm text-slate-900"
                            >
                                <option value="customer" @selected(old('role', 'customer') === 'customer')>Customer</option>
                                <option value="merchant" @selected(old('role') === 'merchant')>Merchant</option>
                            </select>
                            @error('role')
                                <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="password" class="chatgpt-label block text-sm">Password</label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                    class="mt-2 block w-full rounded-2xl border px-4 py-3 text-sm text-slate-900"
                                    required
                                >
                            </div>

                            <div>
                                <label for="password_confirmation" class="chatgpt-label block text-sm">Confirm password</label>
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    class="mt-2 block w-full rounded-2xl border px-4 py-3 text-sm text-slate-900"
                                    required
                                >
                            </div>
                        </div>

                        @error('password')
                            <p class="text-sm text-rose-500">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="admin-primary-button w-full rounded-2xl px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
                            Create account
                        </button>
                    </form>

                    <p class="chatgpt-copy mt-5 text-sm">
                        Already registered?
                        <a href="{{ route('auth.login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Sign in</a>.
                    </p>
                </section>
            </div>
        </div>
    </div>
@endsection
