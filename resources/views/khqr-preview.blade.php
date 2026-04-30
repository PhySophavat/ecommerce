@extends('layouts.application')

@section('content')
@php
    $shop = $merchantName !== '' ? $merchantName : 'Merchant Shop';
    $bank = $bankName !== '' ? $bankName : 'ABA';
    $receiver = $receiverName !== '' ? $receiverName : 'E-commerce KHQR Collection';
    $amountValue = number_format((float) $amount, 2, '.', '');
    $codeValue = $khqrCode !== '' ? $khqrCode : 'KHQR-'.$bank.'|AMOUNT:'.$amountValue;

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
        .'<rect x="80" y="292" width="360" height="360" rx="18" fill="#ffffff"/>'
        .'<g transform="translate(115 327)">'.implode('', $modules).'</g>'
        .'<circle cx="260" cy="507" r="34" fill="#ef2020"/>'
        .'<circle cx="260" cy="507" r="22" fill="#ffffff"/>'
        .'<circle cx="260" cy="507" r="16" fill="#ef2020"/>'
        .'<text x="260" y="708" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#64748b">'.e($bank).'</text>'
        .'</svg>';

    $svgDataUri = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
@endphp
<div style="min-height:100vh;background:
    radial-gradient(circle at top left, rgba(162,95,136,0.16), transparent 30%),
    linear-gradient(180deg,#fff9fc 0%,#f8fafc 100%);
    display:flex;align-items:center;justify-content:center;padding:20px;">
    <div style="width:100%;max-width:320px;background:#ffffff;border:1px solid #f1dbe7;border-radius:26px;padding:16px;box-shadow:0 14px 36px rgba(162,95,136,0.10);">
        <div style="margin-bottom:12px;text-align:center;">
            <div style="display:inline-block;padding:6px 12px;border-radius:999px;background:#fcf1f7;color:#A25F88;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;">
                KHQR Preview
            </div>
        </div>

        <div style="border:1px solid #f1dbe7;border-radius:22px;background:#fff8fc;padding:12px;">
            <img id="khqr-preview-image" src="{{ $svgDataUri }}" alt="KHQR" style="display:block;width:100%;height:auto;border-radius:18px;border:1px solid #f3e4ec;background:#fff;" data-image-token="{{ $imageToken }}">
        </div>

        <div style="margin-top:12px;display:grid;gap:8px;">
            <div style="border:1px solid #e8eef6;border-radius:16px;padding:12px 14px;background:#fff;">
                <div style="font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#94a3b8;font-weight:800;">Amount</div>
                <div style="margin-top:4px;font-size:28px;font-weight:900;color:#A25F88;line-height:1.1;">${{ $amount }}</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div style="border:1px solid #e8eef6;border-radius:16px;padding:12px 14px;background:#fff;">
                    <div style="font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#94a3b8;font-weight:800;">Bank</div>
                    <div style="margin-top:4px;font-size:15px;font-weight:800;color:#0f172a;">{{ $bankName }}</div>
                </div>
                <div style="border:1px solid #e8eef6;border-radius:16px;padding:12px 14px;background:#fff;">
                    <div style="font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#94a3b8;font-weight:800;">Merchant</div>
                    <div style="margin-top:4px;font-size:15px;font-weight:800;color:#0f172a;">{{ $merchantName }}</div>
                </div>
            </div>

            <div style="border:1px solid #e8eef6;border-radius:16px;padding:12px 14px;background:#fff;">
                <div style="font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#94a3b8;font-weight:800;">Receiver</div>
                <div style="margin-top:4px;font-size:15px;font-weight:800;color:#0f172a;line-height:1.4;">{{ $receiverName }}</div>
            </div>

        </div>
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
