@extends('layouts.application')

@section('content')
<div style="min-height:100vh;background:#F8FAFC;display:flex;align-items:center;justify-content:center;padding:1.5rem;">

    <div style="background:#FFFFFF;border:1px solid #E5E7EB;border-radius:20px;padding:2.25rem 2rem 2rem;width:100%;max-width:380px;">

        {{-- Logo --}}
        <div style="width:42px;height:42px;border-radius:11px;background:#A25F88;display:flex;align-items:center;justify-content:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </div>

        {{-- Heading --}}
        <h2 style="margin-top:1.25rem;font-size:22px;font-weight:600;color:#111827;letter-spacing:-0.3px;">OTP Verification</h2>
        <p style="margin-top:4px;font-size:13.5px;color:#6B7280;">Enter the 6-digit code to continue. The current fixed admin OTP is <strong>123456</strong>.</p>

        {{-- OTP Form --}}
        <form method="POST" action="{{ route('auth.otp.verify') }}" style="margin-top:1.5rem;display:flex;flex-direction:column;gap:14px;">
            @csrf

            {{-- OTP Input --}}
            <div>
                <label style="display:block;font-size:13px;font-weight:500;color:#111827;margin-bottom:10px;">OTP code</label>

                <label data-otp-group style="display:block;cursor:text;">
                    <input
                        id="otp"
                        name="otp"
                        type="text"
                        value="{{ old('otp', '123456') }}"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        data-otp-input
                        required
                        style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none;"
                    >
                    <div data-otp-visual style="display:flex;gap:8px;justify-content:center;">
                        @for ($i = 0; $i < 6; $i++)
                            <span
                                data-otp-box
                                data-index="{{ $i }}"
                                style="width:46px;height:52px;border:1px solid #E5E7EB;border-radius:10px;background:#F8FAFC;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:600;color:#111827;transition:border-color 0.15s,box-shadow 0.15s;"
                            ></span>
                        @endfor
                    </div>
                </label>

                @error('otp')
                    <p style="margin-top:8px;font-size:12px;color:#EF4444;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                style="width:100%;background:#A25F88;border:none;border-radius:10px;padding:11.5px;font-size:14px;font-weight:600;color:#fff;cursor:pointer;font-family:inherit;letter-spacing:0.01em;transition:background 0.15s,transform 0.1s;margin-top:2px;"
                onmouseover="this.style.background='#8B4E73';this.style.transform='translateY(-1px)';"
                onmouseout="this.style.background='#A25F88';this.style.transform='none';"
                onmousedown="this.style.transform='scale(0.99)';"
                onmouseup="this.style.transform='translateY(-1px)';"
            >
                Open admin dashboard
            </button>
        </form>

        {{-- Sign out --}}
        <form method="POST" action="{{ route('auth.logout') }}" style="margin-top:10px;">
            @csrf
            <button
                type="submit"
                style="width:100%;background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:11px;font-size:14px;font-weight:500;color:#6B7280;cursor:pointer;font-family:inherit;transition:background 0.15s,color 0.15s,transform 0.1s;"
                onmouseover="this.style.background='#F8FAFC';this.style.color='#111827';this.style.transform='translateY(-1px)';"
                onmouseout="this.style.background='#fff';this.style.color='#6B7280';this.style.transform='none';"
                onmousedown="this.style.transform='scale(0.99)';"
            >
                Sign out
            </button>
        </form>

    </div>
</div>

<script>
(() => {
    const group = document.querySelector('[data-otp-group]');
    if (!group) return;

    const input = group.querySelector('[data-otp-input]');
    const boxes = Array.from(group.querySelectorAll('[data-otp-box]'));
    if (!input || boxes.length === 0) return;

    const activeStyle  = 'border-color:#A25F88;box-shadow:0 0 0 3px rgba(162,95,136,0.12);background:#fff;';
    const filledStyle  = 'border-color:#A25F88;background:#fff;';
    const defaultStyle = 'border-color:#E5E7EB;background:#F8FAFC;box-shadow:none;';

    const render = () => {
        const digits = String(input.value ?? '').replace(/\D/g, '').slice(0, boxes.length);
        if (input.value !== digits) input.value = digits;

        const isFocused  = document.activeElement === input;
        const activeIndex = digits.length >= boxes.length ? boxes.length - 1 : digits.length;

        boxes.forEach((box, i) => {
            const digit = digits[i] ?? '';
            box.textContent = digit;
            const isActive = isFocused && i === activeIndex;
            box.style.cssText = box.style.cssText.replace(/border-color:[^;]+;|box-shadow:[^;]+;|background:[^;]+;/g, '');
            if (isActive) {
                box.style.borderColor = '#A25F88';
                box.style.boxShadow   = '0 0 0 3px rgba(162,95,136,0.12)';
                box.style.background  = '#fff';
            } else if (digit) {
                box.style.borderColor = '#A25F88';
                box.style.background  = '#fff';
                box.style.boxShadow   = 'none';
            } else {
                box.style.borderColor = '#E5E7EB';
                box.style.background  = '#F8FAFC';
                box.style.boxShadow   = 'none';
            }
        });
    };

    group.addEventListener('click', () => input.focus());
    input.addEventListener('focus', render);
    input.addEventListener('blur', render);
    input.addEventListener('input', render);
    input.addEventListener('paste', () => requestAnimationFrame(render));

    render();
})();
</script>
@endsection
