@extends('layouts.application')

@section('content')
@php
    $shop = $merchantName !== '' ? $merchantName : 'Merchant Shop';
    $bank = $bankName !== '' ? $bankName : 'ABA';
    $receiver = $receiverName !== '' ? $receiverName : 'E-commerce KHQR Collection';
    $amountValue = number_format((float) $amount, 2, '.', '');
    $codeValue = $khqrCode !== '' ? $khqrCode : 'KHQR-'.$bank.'|AMOUNT:'.$amountValue;
    $bankKey = strtoupper(trim($bank));
    $bankBadge = match ($bankKey) {
        'ABA' => ['bg' => '#0F766E', 'fg' => '#FFFFFF', 'text' => 'ABA'],
        'ACLEDA' => ['bg' => '#1D4ED8', 'fg' => '#FFFFFF', 'text' => 'AC'],
        'WING' => ['bg' => '#16A34A', 'fg' => '#FFFFFF', 'text' => 'WG'],
        'CARD' => ['bg' => '#7C3AED', 'fg' => '#FFFFFF', 'text' => 'CD'],
        'CASH' => ['bg' => '#F59E0B', 'fg' => '#FFFFFF', 'text' => 'CA'],
        default => ['bg' => '#A25F88', 'fg' => '#FFFFFF', 'text' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $bankKey), 0, 2) ?: 'BK')],
    };

    $size = 29;
    $modules = [];

    $isFinder = function (int $x, int $y, int $grid): bool {
        $topLeft = $x < 7 && $y < 7;
        $topRight = $x >= $grid - 7 && $y < 7;
        $bottomLeft = $x < 7 && $y >= $grid - 7;

        return $topLeft || $topRight || $bottomLeft;
    };

    $hash = function (string $value): int {
        $result = 0;

        for ($index = 0; $index < strlen($value); $index++) {
            $result = (($result << 5) - $result) + ord($value[$index]);
            $result &= 0x7fffffff;
        }

        return abs($result);
    };

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $seed = $codeValue.'-'.$x.'-'.$y;
            $active = $isFinder($x, $y, $size) || ($hash($seed) % 2 === 0);

            if ($active) {
                $modules[] = '<rect x="'.($x * 10).'" y="'.($y * 10).'" width="10" height="10" fill="#111111" />';
            }
        }
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="520" height="760" viewBox="0 0 520 760">'
        .'<rect width="520" height="760" rx="36" fill="#ffffff"/>'
        .'<rect x="54" y="48" width="412" height="74" rx="24" fill="#ee1717"/>'
        .'<text x="260" y="93" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="800" fill="#ffffff">KHQR</text>'
        .'<text x="92" y="176" font-family="Arial, sans-serif" font-size="20" font-weight="800" fill="#111111">'.e(mb_strimwidth($shop, 0, 22, '')).'</text>'
        .'<text x="92" y="230" font-family="Arial, sans-serif" font-size="54" font-weight="900" fill="#111111">$'.e($amountValue).'</text>'
        .'<line x1="54" y1="266" x2="466" y2="266" stroke="#cbd5e1" stroke-dasharray="8 8" stroke-width="2"/>'
        .'<rect x="362" y="148" width="82" height="82" rx="24" fill="'.e($bankBadge['bg']).'"/>'
        .'<text x="403" y="198" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="800" fill="'.e($bankBadge['fg']).'">'.e($bankBadge['text']).'</text>'
        .'<rect x="80" y="292" width="360" height="360" rx="18" fill="#ffffff"/>'
        .'<g transform="translate(115 327)">'.implode('', $modules).'</g>'
        .'<circle cx="260" cy="507" r="34" fill="#ef2020"/>'
        .'<circle cx="260" cy="507" r="22" fill="#ffffff"/>'
        .'<circle cx="260" cy="507" r="16" fill="#ef2020"/>'
        .'<text x="260" y="708" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#64748b">'.e($bank).'</text>'
        .'</svg>';

    $svgDataUri = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
@endphp
<div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
    <div class="mx-auto flex min-h-[calc(100vh-1.5rem)] w-full max-w-[760px] items-center justify-center px-4 py-6 sm:px-6">
        <section class="w-full max-w-[440px] rounded-[24px] border border-[#ead9e3] bg-white p-4 shadow-[0_20px_60px_rgba(58,74,145,0.10)]">
            <div class="flex flex-col gap-4">
                <div class="rounded-[20px] border border-[#f0d9e6] bg-[#fff9fc] p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="chatgpt-kicker text-[11px] uppercase text-[#A25F88]">KHQR Preview</p>
                            <h1 class="chatgpt-title mt-1.5 text-xl font-extrabold text-slate-950">Scan and pay</h1>
                        </div>
                        <span class="admin-chip rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                            {{ $bank }}
                        </span>
                    </div>

                    <div class="mt-4 rounded-[18px] border border-[#f0d9e6] bg-white p-3">
                        <img
                            id="khqr-preview-image"
                            src="{{ $svgDataUri }}"
                            alt="KHQR"
                            class="block w-full rounded-[18px] border border-[#f3e4ec] bg-white"
                            data-image-token="{{ $imageToken }}"
                        >
                    </div>

                    <div class="mt-3 rounded-[16px] border border-[#ead9e3] bg-white px-4 py-3">
                        <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Amount</p>
                        <p class="mt-1.5 text-[1.75rem] font-extrabold tracking-[-0.05em] text-[#A25F88]">${{ $amount }}</p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="rounded-[16px] border border-slate-200 bg-slate-50 px-3.5 py-3">
                        <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Shop</p>
                        <p class="mt-2 text-sm font-bold text-slate-950">{{ $shop }}</p>
                    </article>

                    <article class="rounded-[16px] border border-slate-200 bg-slate-50 px-3.5 py-3">
                        <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Bank</p>
                        <p class="mt-2 text-sm font-bold text-slate-950">{{ $bank }}</p>
                    </article>

                    <article class="rounded-[16px] border border-slate-200 bg-slate-50 px-3.5 py-3 sm:col-span-2">
                        <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Receiver</p>
                        <p class="mt-2 text-sm font-bold text-slate-950">{{ $receiver }}</p>
                    </article>
                </div>
            </div>
        </section>
    </div>
</div>
@if ($imageToken !== '')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const image = document.getElementById('khqr-preview-image');
    if (!image) return;

    const token = image.dataset.imageToken;
    if (!token) return;

    const stored = window.localStorage.getItem(`khqr_preview:${token}`);
    if (stored) {
        image.src = stored;
    }
});
</script>
@endif
@endsection
