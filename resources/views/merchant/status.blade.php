@extends('layouts.application')

@section('content')
<div class="min-h-screen bg-slate-950 px-4 py-12 text-white">
    <div class="mx-auto max-w-3xl">
        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-8 shadow-2xl">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Merchant Review</p>
                    <h1 class="mt-2 text-3xl font-bold">{{ $merchant->shop_name }}</h1>
                </div>
                <span class="rounded-full px-4 py-2 text-sm font-semibold
                    @class([
                        'bg-amber-500/15 text-amber-300' => $merchant->status === 'Pending',
                        'bg-rose-500/15 text-rose-300' => $merchant->status === 'Rejected',
                        'bg-orange-500/15 text-orange-300' => $merchant->status === 'Suspended',
                    ])">
                    {{ $merchant->status }}
                </span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                    <p class="text-sm text-slate-400">Owner</p>
                    <p class="mt-2 text-lg font-semibold">{{ $merchant->user->name }}</p>
                    <p class="text-slate-300">{{ $merchant->user->email }}</p>
                    <p class="text-slate-300">{{ $merchant->user->phone ?? '-' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                    <p class="text-sm text-slate-400">Verification</p>
                    <p class="mt-2 text-lg font-semibold">{{ $merchant->verification_status }}</p>
                    <p class="text-slate-300">{{ $merchant->location?->province_city ?? '-' }}</p>
                    <p class="text-slate-300">{{ $merchant->location?->full_address ?? '-' }}</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                <p class="text-sm text-slate-400">Status Message</p>
                @if($merchant->status === 'Pending')
                    <p class="mt-2 text-slate-200">Your registration has been submitted. Admin approval is required before you can access merchant product management.</p>
                @elseif($merchant->status === 'Rejected')
                    <p class="mt-2 text-slate-200">Your registration was rejected. Review the reason below and contact admin before reapplying.</p>
                @else
                    <p class="mt-2 text-slate-200">Your merchant account is temporarily suspended. Please contact admin for the next step.</p>
                @endif

                @if($merchant->rejection_reason)
                    <div class="mt-4 rounded-2xl border border-rose-900 bg-rose-950/40 p-4 text-rose-200">
                        {{ $merchant->rejection_reason }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
