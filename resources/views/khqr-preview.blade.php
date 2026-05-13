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
<div class="chatgpt-admin min-h-screen bg-[#F8FAFC] px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
    <div class="mx-auto flex min-h-[calc(100vh-3rem)] w-full max-w-[640px] items-center justify-center">
        <section class="w-full rounded-[28px] border border-[#E5E7EB] bg-[#FFFFFF] px-5 py-5 shadow-[0_18px_48px_rgba(17,24,39,0.08)] sm:px-6 sm:py-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#A25F88]">KHQR Preview</p>
                    <h1 class="mt-2 text-[1.9rem] font-extrabold tracking-[-0.04em] text-[#111827] sm:text-[2rem]">Scan and pay</h1>
                </div>
                <span class="inline-flex items-center rounded-full border border-[#E5E7EB] bg-[#FFFFFF] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88] shadow-sm">
                    {{ $bank }}
                </span>
            </div>

            <div class="mt-5 rounded-[24px] border border-[#E5E7EB] bg-[#F9FAFB] p-4 shadow-[0_10px_30px_rgba(17,24,39,0.05)] sm:p-5">
                <div class="mx-auto flex w-full max-w-[272px] items-center justify-center rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-3 shadow-[0_12px_28px_rgba(17,24,39,0.06)] sm:max-w-[290px]">
                    <img
                        id="khqr-preview-image"
                        src="{{ $svgDataUri }}"
                        alt="KHQR"
                        class="mx-auto h-auto w-full rounded-[20px] border border-[#E5E7EB] bg-[#FFFFFF] object-contain"
                        data-image-token="{{ $imageToken }}"
                    >
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <article class="rounded-[18px] border border-[#E5E7EB] bg-[#F9FAFB] px-4 py-3.5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Shop</p>
                        <p class="mt-2 text-base font-semibold leading-6 text-[#111827]">{{ $shop }}</p>
                    </article>
                    <article class="rounded-[18px] border border-[#E5E7EB] bg-[#F9FAFB] px-4 py-3.5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Amount</p>
                        <p class="mt-2 text-base font-semibold leading-6 text-[#111827]">${{ $amount }}</p>
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
