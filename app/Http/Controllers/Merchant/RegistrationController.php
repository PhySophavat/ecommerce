<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\User;
use App\Support\AdminDashboardData;
use App\Support\DashboardAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    private const BUSINESS_TYPES = ['Fashion', 'Beauty', 'Electronic', 'Sport', 'Home', 'Food', 'Health', 'Book', 'Toy', 'Other'];

    private const PROVINCES = [
        'Phnom Penh',
        'Banteay Meanchey',
        'Battambang',
        'Kampong Cham',
        'Kampong Chhnang',
        'Kampong Speu',
        'Kampot',
        'Kandal',
        'Kep',
        'Koh Kong',
        'Kratie',
        'Mondul Kiri',
        'Oddor Meanchey',
        'Pailin',
        'Preah Vihear',
        'Prey Veng',
        'Pursat',
        'Ratanak Kiri',
        'Siem Reap',
        'Sihanoukville',
        'Stung Treng',
        'Svay Rieng',
        'Takeo',
    ];

    public function step1(Request $request): View|RedirectResponse
    {
        if (auth()->check() && auth()->user()->merchant) {
            return redirect()->route('merchant.status');
        }

        return $this->page($request, 'step1', 'Register as Merchant - Step 1: Business Info');
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255|unique:merchants,shop_name',
            'business_type' => 'required|string|in:Fashion,Beauty,Electronic,Sport,Home,Food,Health,Book,Toy,Other',
            'business_description' => 'nullable|string|max:1000',
            'shop_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        $step1 = session('merchant_registration.step1', []);

        if ($request->hasFile('shop_logo')) {
            $this->deleteStoredFile($step1['shop_logo'] ?? null);
            $validated['shop_logo'] = $request->file('shop_logo')->store('merchants/logos', 'public');
        } else {
            $validated['shop_logo'] = $step1['shop_logo'] ?? null;
        }

        if ($request->hasFile('cover_image')) {
            $this->deleteStoredFile($step1['cover_image'] ?? null);
            $validated['cover_image'] = $request->file('cover_image')->store('merchants/covers', 'public');
        } else {
            $validated['cover_image'] = $step1['cover_image'] ?? null;
        }

        session()->put('merchant_registration.step1', $validated);

        return redirect()->route('merchant.register.step2');
    }

    public function step2(Request $request): View|RedirectResponse
    {
        if (!session()->has('merchant_registration.step1')) {
            return redirect()->route('merchant.register.step1');
        }

        if (auth()->check() && auth()->user()->merchant) {
            return redirect()->route('merchant.status');
        }

        return $this->page($request, 'step2', 'Register as Merchant - Step 2: Owner Info');
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        if (!session()->has('merchant_registration.step1')) {
            return redirect()->route('merchant.register.step1');
        }

        $validated = $request->validate([
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s]+$/',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $step2 = session('merchant_registration.step2', []);

        if (User::query()->where('phone', $validated['phone'])->exists()) {
            return back()
                ->withErrors(['phone' => 'The phone number has already been taken.'])
                ->withInput();
        }

        if ($request->hasFile('profile_image')) {
            $this->deleteStoredFile($step2['profile_image'] ?? null);
            $validated['profile_image'] = $request->file('profile_image')->store('merchants/profiles', 'public');
        } else {
            $validated['profile_image'] = $step2['profile_image'] ?? null;
        }

        if ($request->hasFile('id_card')) {
            $this->deleteStoredFile($step2['id_card'] ?? null);
            $validated['id_card'] = $request->file('id_card')->store('merchants/documents', 'public');
        } else {
            $validated['id_card'] = $step2['id_card'] ?? null;
        }

        session()->put('merchant_registration.step2', $validated);

        return redirect()->route('merchant.register.step3');
    }

    public function step3(Request $request): View|RedirectResponse
    {
        if (!session()->has('merchant_registration.step1') || !session()->has('merchant_registration.step2')) {
            return redirect()->route('merchant.register.step1');
        }

        if (auth()->check() && auth()->user()->merchant) {
            return redirect()->route('merchant.status');
        }

        return $this->page($request, 'step3', 'Register as Merchant - Step 3: Location');
    }

    public function storeStep3(Request $request): RedirectResponse
    {
        if (!session()->has('merchant_registration.step1') || !session()->has('merchant_registration.step2')) {
            return redirect()->route('merchant.register.step1');
        }

        $validated = $request->validate([
            'full_address' => 'required|string|max:500',
            'province_city' => 'required|string|max:100',
            'district' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'google_map_link' => 'nullable|url|max:500',
            'delivery_area' => 'nullable|string|max:500',
        ]);

        $step1 = session()->get('merchant_registration.step1');
        $step2 = session()->get('merchant_registration.step2');

        session()->put('merchant_registration.step3', $validated);

        DB::transaction(function () use ($step1, $step2, $validated) {
            $user = User::create([
                'name' => $step2['owner_name'],
                'email' => $step2['email'],
                'phone' => $step2['phone'],
                'profile_image' => $step2['profile_image'] ?? null,
                'password' => Hash::make($step2['password']),
                'role' => 'merchant',
            ]);

            $merchant = Merchant::create([
                'user_id' => $user->id,
                'shop_name' => $step1['shop_name'],
                'business_type' => $step1['business_type'],
                'business_description' => $step1['business_description'] ?? null,
                'shop_logo' => $step1['shop_logo'] ?? null,
                'cover_image' => $step1['cover_image'] ?? null,
                'id_card_document' => $step2['id_card'] ?? null,
                'verification_status' => 'Pending',
                'status' => 'Pending',
            ]);

            MerchantLocation::create([
                'merchant_id' => $merchant->id,
                'full_address' => $validated['full_address'],
                'province_city' => $validated['province_city'],
                'district' => $validated['district'] ?? null,
                'commune' => $validated['commune'] ?? null,
                'google_map_link' => $validated['google_map_link'] ?? null,
                'delivery_area' => $validated['delivery_area'] ?? null,
            ]);
        });

        session()->forget('merchant_registration');

        return redirect()->route('login')->with('success', 'Your merchant registration has been submitted! Please wait for admin approval.');
    }

    public function status(Request $request): View|RedirectResponse
    {
        $merchant = $request->user()?->merchant;

        if (!$merchant) {
            return redirect()->route('merchant.register.step1');
        }

        return view('merchant.status', [
            'title' => 'Merchant | Profile',
            'context' => [
                'app' => 'merchant-status',
                'screen' => 'merchant-status',
                'merchant' => [
                    'shop_name' => $merchant->shop_name,
                    'business_type' => $merchant->business_type,
                    'business_description' => $merchant->business_description,
                    'shop_logo' => $merchant->shop_logo,
                    'cover_image' => $merchant->cover_image,
                    'id_card_document' => $merchant->id_card_document,
                    'status' => $merchant->status,
                    'verification_status' => $merchant->verification_status,
                    'rejection_reason' => $merchant->rejection_reason,
                    'created_at' => $merchant->created_at?->toIso8601String(),
                    'owner' => [
                        'name' => $merchant->user?->name,
                        'email' => $merchant->user?->email,
                        'phone' => $merchant->user?->phone,
                        'profile_image' => $merchant->user?->profile_image,
                    ],
                    'location' => [
                        'province_city' => $merchant->location?->province_city,
                        'full_address' => $merchant->location?->full_address,
                        'district' => $merchant->location?->district,
                        'commune' => $merchant->location?->commune,
                        'google_map_link' => $merchant->location?->google_map_link,
                        'delivery_area' => $merchant->location?->delivery_area,
                    ],
                ],
                'meta' => [
                    'brand' => 'E-commerce',
                    'page_title' => 'Merchant Profile',
                    'kicker' => 'Profile',
                    'subheadline' => 'View your merchant profile information.',
                ],
                'menu' => DashboardAccess::menuTreeForRole(
                    $request->user()?->role ?? 'merchant',
                    DashboardAccess::activeSlugsForScreen('merchant-status')
                ),
            ],
        ]);
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function page(Request $request, string $step, string $title): View
    {
        $step1 = session('merchant_registration.step1', []);
        $step2 = session('merchant_registration.step2', []);
        $step3 = session('merchant_registration.step3', []);
        $errors = session('errors')?->getBag('default')->toArray() ?? [];
        $adminDashboard = AdminDashboardData::accountsIndex('merchants');

        return view('merchant.register.index', [
            'title' => $title,
            'context' => [
                'app' => 'backend-merchant-register',
                'step' => $step,
                'csrfToken' => csrf_token(),
                'routes' => [
                    'step1' => route('merchant.register.step1'),
                    'step1Store' => route('merchant.register.step1.store'),
                    'step2' => route('merchant.register.step2'),
                    'step2Store' => route('merchant.register.step2.store'),
                    'step3' => route('merchant.register.step3'),
                    'step3Store' => route('merchant.register.step3.store'),
                ],
                'options' => [
                    'businessTypes' => self::BUSINESS_TYPES,
                    'provinces' => self::PROVINCES,
                ],
                'dashboard' => [
                    'meta' => [
                        ...$adminDashboard['meta'],
                        'page_title' => 'Create Merchant',
                        'kicker' => 'Seller management',
                        'subheadline' => 'Create merchant accounts in three small steps with the same admin navigation and all backend features visible.',
                    ],
                    'menu' => $adminDashboard['menu'],
                ],
                'errors' => $errors,
                'form' => [
                    'step1' => [
                        'shop_name' => old('shop_name', $step1['shop_name'] ?? ''),
                        'business_type' => old('business_type', $step1['business_type'] ?? ''),
                        'business_description' => old('business_description', $step1['business_description'] ?? ''),
                        'shop_logo_uploaded' => !empty($step1['shop_logo']),
                        'cover_image_uploaded' => !empty($step1['cover_image']),
                    ],
                    'step2' => [
                        'owner_name' => old('owner_name', $step2['owner_name'] ?? ''),
                        'phone' => old('phone', $step2['phone'] ?? ''),
                        'email' => old('email', $step2['email'] ?? ''),
                        'profile_image_uploaded' => !empty($step2['profile_image']),
                        'id_card_uploaded' => !empty($step2['id_card']),
                    ],
                    'step3' => [
                        'full_address' => old('full_address', $step3['full_address'] ?? ''),
                        'province_city' => old('province_city', $step3['province_city'] ?? ''),
                        'district' => old('district', $step3['district'] ?? ''),
                        'commune' => old('commune', $step3['commune'] ?? ''),
                        'google_map_link' => old('google_map_link', $step3['google_map_link'] ?? ''),
                        'delivery_area' => old('delivery_area', $step3['delivery_area'] ?? ''),
                    ],
                ],
            ],
        ]);
    }
}
