@extends('layouts.application')

@section('content')
<div style="min-height:100vh;background:#F8FAFC;display:flex;align-items:center;justify-content:center;padding:1.5rem;">

    <div style="background:#FFFFFF;border:1px solid #E5E7EB;border-radius:20px;padding:2.25rem 2rem 2rem;width:100%;max-width:380px;">

        {{-- Logo --}}
        <div style="width:42px;height:42px;border-radius:11px;background:#A25F88;display:flex;align-items:center;justify-content:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
        </div>

        {{-- Heading --}}
        <h2 style="margin-top:1.25rem;font-size:22px;font-weight:600;color:#111827;letter-spacing:-0.3px;">Sign in</h2>
        <p style="margin-top:4px;font-size:13.5px;color:#6B7280;">Admin dashboard</p>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.login.submit') }}" style="margin-top:1.5rem;display:flex;flex-direction:column;gap:14px;">
            @csrf

            {{-- Username --}}
            <div>
                <label for="login" style="display:block;font-size:13px;font-weight:500;color:#111827;margin-bottom:6px;">Username</label>
                <input
                    id="login"
                    name="login"
                    type="text"
                    value="{{ old('login') }}"
                    autocomplete="username"
                    placeholder="admin"
                    required
                    style="width:100%;background:#F8FAFC;border:1px solid #E5E7EB;border-radius:10px;padding:10px 14px;font-size:14px;color:#111827;outline:none;font-family:inherit;transition:border-color 0.15s,box-shadow 0.15s;"
                    onfocus="this.style.borderColor='#A25F88';this.style.boxShadow='0 0 0 3px rgba(162,95,136,0.12)';this.style.background='#fff';"
                    onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none';this.style.background='#F8FAFC';"
                >
                @error('login')
                    <p style="margin-top:6px;font-size:12px;color:#EF4444;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" style="display:block;font-size:13px;font-weight:500;color:#111827;margin-bottom:6px;">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    required
                    style="width:100%;background:#F8FAFC;border:1px solid #E5E7EB;border-radius:10px;padding:10px 14px;font-size:14px;color:#111827;outline:none;font-family:inherit;transition:border-color 0.15s,box-shadow 0.15s;"
                    onfocus="this.style.borderColor='#A25F88';this.style.boxShadow='0 0 0 3px rgba(162,95,136,0.12)';this.style.background='#fff';"
                    onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none';this.style.background='#F8FAFC';"
                >
                @error('password')
                    <p style="margin-top:6px;font-size:12px;color:#EF4444;">{{ $message }}</p>
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
                Continue to OTP
            </button>
        </form>

        {{-- Footer --}}
        <p style="margin-top:1.25rem;text-align:center;font-size:13px;color:#6B7280;">
            Don't have an account?
            <a href="{{ route('register') }}" style="color:#A25F88;font-weight:500;text-decoration:none;"
               onmouseover="this.style.color='#8B4E73';"
               onmouseout="this.style.color='#A25F88';">
                Register
            </a>
        </p>

    </div>
</div>
@endsection