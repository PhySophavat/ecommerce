<?php

namespace App\Http\Controllers\Backend\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Support\AdminDashboardData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Merchant::with(['user', 'location', 'approver'])
            ->withCount([
                'products',
                'products as pending_products_count' => fn ($query) => $query->where('status', 'pending'),
                'products as approved_products_count' => fn ($query) => $query->where('status', 'approved'),
            ])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by business type
        if ($request->has('business_type') && $request->business_type) {
            $query->where('business_type', $request->business_type);
        }

        // Search by shop name or owner name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $merchants = $query->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($this->indexPayload($merchants));
        }

        return $this->page('merchants', 'Admin | Merchants');
    }

    public function pending(Request $request): View|JsonResponse
    {
        $query = Merchant::with(['user', 'location'])
            ->withCount([
                'products',
                'products as pending_products_count' => fn ($query) => $query->where('status', 'pending'),
                'products as approved_products_count' => fn ($query) => $query->where('status', 'approved'),
            ])
            ->where('status', 'Pending')
            ->orderBy('created_at', 'desc');

        $merchants = $query->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($this->pendingPayload($merchants));
        }

        return $this->page('pending-merchants', 'Admin | Pending Merchants');
    }

    public function show(Request $request, Merchant $merchant): JsonResponse|View
    {
        $merchant->load(['user', 'location', 'approver'])
            ->loadCount([
                'products',
                'products as pending_products_count' => fn ($query) => $query->where('status', 'pending'),
                'products as approved_products_count' => fn ($query) => $query->where('status', 'approved'),
            ]);

        if ($request->expectsJson()) {
            return response()->json($this->showPayload($merchant));
        }

        return $this->page('merchant-details', 'Admin | Merchant Details', $merchant);
    }

    /**
     * Approve a merchant.
     */
    public function approve(Request $request, Merchant $merchant): RedirectResponse|JsonResponse
    {
        $merchant->update([
            'status' => 'Approved',
            'verification_status' => 'Verified',
            'rejection_reason' => null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Merchant {$merchant->shop_name} has been approved.",
                'merchant' => $this->merchantPayload($merchant->fresh()),
            ]);
        }

        return redirect()->back()->with('success', "Merchant {$merchant->shop_name} has been approved.");
    }

    /**
     * Reject a merchant.
     */
    public function reject(Request $request, Merchant $merchant): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $merchant->update([
            'status' => 'Rejected',
            'verification_status' => 'Not Verified',
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Merchant {$merchant->shop_name} has been rejected.",
                'merchant' => $this->merchantPayload($merchant->fresh()),
            ]);
        }

        return redirect()->back()->with('success', "Merchant {$merchant->shop_name} has been rejected.");
    }

    /**
     * Suspend a merchant.
     */
    public function suspend(Request $request, Merchant $merchant): RedirectResponse|JsonResponse
    {
        $merchant->update([
            'status' => 'Suspended',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Merchant {$merchant->shop_name} has been suspended.",
                'merchant' => $this->merchantPayload($merchant->fresh()),
            ]);
        }

        return redirect()->back()->with('success', "Merchant {$merchant->shop_name} has been suspended.");
    }

    /**
     * Reactivate a suspended merchant.
     */
    public function reactivate(Request $request, Merchant $merchant): RedirectResponse|JsonResponse
    {
        $merchant->update([
            'status' => 'Approved',
            'verification_status' => 'Verified',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Merchant {$merchant->shop_name} has been reactivated.",
                'merchant' => $this->merchantPayload($merchant->fresh()),
            ]);
        }

        return redirect()->back()->with('success', "Merchant {$merchant->shop_name} has been reactivated.");
    }

    /**
     * Get merchant statistics for dashboard.
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->merchantStats());
    }

    /**
     * Transform merchant to JSON payload.
     */
    private function merchantPayload(Merchant $merchant, bool $detailed = false): array
    {
        $data = [
            'id' => $merchant->id,
            'shop_name' => $merchant->shop_name,
            'business_type' => $merchant->business_type,
            'business_description' => $merchant->business_description,
            'shop_logo' => $merchant->shop_logo,
            'cover_image' => $merchant->cover_image,
            'id_card_document' => $merchant->id_card_document,
            'verification_status' => $merchant->verification_status,
            'status' => $merchant->status,
            'rejection_reason' => $merchant->rejection_reason,
            'products_count' => (int) ($merchant->products_count ?? 0),
            'pending_products_count' => (int) ($merchant->pending_products_count ?? 0),
            'approved_products_count' => (int) ($merchant->approved_products_count ?? 0),
            'created_at' => $merchant->created_at->toIso8601String(),
            'approved_at' => $merchant->approved_at?->toIso8601String(),
            'user' => [
                'id' => $merchant->user->id,
                'name' => $merchant->user->name,
                'email' => $merchant->user->email,
                'phone' => $merchant->user->phone,
                'profile_image' => $merchant->user->profile_image,
            ],
        ];

        if ($detailed) {
            $data['location'] = $merchant->location ? [
                'full_address' => $merchant->location->full_address,
                'province_city' => $merchant->location->province_city,
                'district' => $merchant->location->district,
                'commune' => $merchant->location->commune,
                'google_map_link' => $merchant->location->google_map_link,
                'delivery_area' => $merchant->location->delivery_area,
            ] : null;

            $data['approver'] = $merchant->approver ? [
                'id' => $merchant->approver->id,
                'name' => $merchant->approver->name,
            ] : null;
        }

        return $data;
    }

    private function page(string $screen, string $title, ?Merchant $merchant = null): View
    {
        return view('backend.merchants.index', [
            'title' => $title,
            'context' => [
                'app' => 'backend-merchants',
                'screen' => $screen,
                'endpoint' => match ($screen) {
                    'pending-merchants' => route('admin.merchants.pending'),
                    'merchant-details' => $merchant ? route('admin.merchants.show', $merchant) : route('admin.merchants.index'),
                    default => route('admin.merchants.index'),
                },
                'merchantId' => $merchant?->id,
            ],
        ]);
    }

    private function pendingPayload($merchants): array
    {
        $base = AdminDashboardData::accountsIndex('merchants');

        return [
            ...$base,
            'screen' => 'pending-merchants',
            'meta' => [
                ...$base['meta'],
                'page_title' => 'Pending Merchants',
                'kicker' => 'Merchant approval queue',
                'subheadline' => 'Review registrations waiting for approval before sellers can access the merchant area.',
            ],
            'menu' => $this->normalizeMerchantMenu($base['menu']),
            'stats' => $this->merchantStats(),
            'merchants' => $merchants->map(fn ($merchant) => $this->merchantPayload($merchant, true))->values()->all(),
            'pagination' => [
                'current_page' => $merchants->currentPage(),
                'last_page' => $merchants->lastPage(),
                'per_page' => $merchants->perPage(),
                'total' => $merchants->total(),
            ],
        ];
    }

    private function indexPayload($merchants): array
    {
        $base = AdminDashboardData::accountsIndex('merchants');

        return [
            ...$base,
            'screen' => 'merchants',
            'meta' => [
                ...$base['meta'],
                'page_title' => 'Merchants',
                'kicker' => 'Seller management',
                'subheadline' => 'Create merchant accounts, review their store information, and manage seller access from one place.',
            ],
            'menu' => $this->normalizeMerchantMenu($base['menu']),
            'stats' => $this->merchantStats(),
            'merchants' => $merchants->map(fn ($merchant) => $this->merchantPayload($merchant, true))->values()->all(),
            'pagination' => [
                'current_page' => $merchants->currentPage(),
                'last_page' => $merchants->lastPage(),
                'per_page' => $merchants->perPage(),
                'total' => $merchants->total(),
            ],
        ];
    }

    private function showPayload(Merchant $merchant): array
    {
        $base = AdminDashboardData::accountsIndex('merchants');

        return [
            ...$base,
            'screen' => 'merchant-details',
            'meta' => [
                ...$base['meta'],
                'page_title' => 'Merchant Details',
                'kicker' => 'Merchant review',
                'subheadline' => 'Inspect merchant, owner, and location information before taking action.',
            ],
            'menu' => $this->normalizeMerchantMenu($base['menu']),
            'stats' => $this->merchantStats(),
            'merchant' => $this->merchantPayload($merchant, true),
        ];
    }

    private function activateMenuItems(array $menuItems, array $activeSlugs): array
    {
        return array_map(function (array $item) use ($activeSlugs) {
            $children = isset($item['children']) && is_array($item['children'])
                ? $this->activateMenuItems($item['children'], $activeSlugs)
                : [];

            $childIsActive = collect($children)->contains(fn (array $child): bool => (bool) ($child['is_active'] ?? false));
            $isActive = in_array($item['slug'] ?? null, $activeSlugs, true) || $childIsActive;

            return [
                ...$item,
                'children' => $children,
                'is_active' => $isActive,
                'is_expanded' => ($item['is_expanded'] ?? false) || $isActive,
            ];
        }, $menuItems);
    }

    private function normalizeMerchantMenu(array $menuItems): array
    {
        $menuItems = $this->activateMenuItems($menuItems, ['users-admin-management', 'merchants']);

        if (isset($menuItems[8]['children'][1])) {
            $menuItems[8]['is_active'] = true;
            $menuItems[8]['is_expanded'] = true;
            $menuItems[8]['children'][1]['is_active'] = true;
        }

        return $menuItems;
    }

    private function merchantStats(): array
    {
        return [
            'total' => Merchant::count(),
            'pending' => Merchant::where('status', 'Pending')->count(),
            'approved' => Merchant::where('status', 'Approved')->count(),
            'rejected' => Merchant::where('status', 'Rejected')->count(),
            'suspended' => Merchant::where('status', 'Suspended')->count(),
        ];
    }
}
