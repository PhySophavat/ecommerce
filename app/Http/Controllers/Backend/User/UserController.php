<?php

namespace App\Http\Controllers\Backend\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminDashboardData;
use App\Support\StorefrontData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        return $this->screen($request, 'users', 'Admin | Users');
    }

    public function merchants(Request $request): View|JsonResponse
    {
        return $this->screen($request, 'merchants', 'Admin | Merchants');
    }

    public function customers(Request $request): View|JsonResponse
    {
        return $this->screen($request, 'customers', 'Admin | Customers');
    }

    public function customerDetails(Request $request): View|JsonResponse
    {
        return $this->screen($request, 'customer-details', 'Admin | Customer Details');
    }

    public function purchaseHistory(Request $request): View|JsonResponse
    {
        return $this->screen($request, 'purchase-history', 'Admin | Customer Purchase History');
    }

    public function create(Request $request): View
    {
        $screen = $this->screenForRole((string) $request->query('role', 'admin'));

        return $this->page($screen, $screen === 'merchants' ? 'Admin | Create Merchant' : 'Admin | Create User');
    }

    public function edit(Request $request, User $user): JsonResponse|View
    {
        if ($request->expectsJson()) {
            return response()->json([
                'user' => $this->userPayload($user->fresh()),
            ]);
        }

        $screen = $this->screenForRole($user->role);

        return $this->page(
            $screen,
            match ($screen) {
                'merchants' => 'Admin | Merchants',
                'customers' => 'Admin | Customers',
                'customer-details' => 'Admin | Customer Details',
                default => 'Admin | Users',
            },
        );
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', Rule::in(['admin', 'merchant', 'customer'])],
        ]);

        $role = $validated['role'] ?? 'customer';
        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $role,
        ]);
        $user->created_by = $request->user()?->getKey();
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$user->name} was created successfully.",
                'user' => $this->userPayload($user),
            ], 201);
        }

        $route = match ($role) {
            'admin' => 'admin.users.index',
            'merchant' => 'admin.merchants.index',
            'customer' => 'admin.customers.index',
            default => 'admin.products.index',
        };

        return redirect()
            ->route($route)
            ->with('status', "{$user->name} was created successfully.");
    }

    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', Rule::in(['admin', 'merchant', 'customer'])],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'] ?? $user->role;

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$user->name} was updated successfully.",
                'user' => $this->userPayload($user->fresh()),
            ]);
        }

        return redirect()
            ->route(match (true) {
                $user->isMerchant() => 'admin.merchants.index',
                $user->isCustomer() => 'admin.customers.index',
                default => 'admin.users.index',
            })
            ->with('status', "{$user->name} was updated successfully.");
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ((int) $request->user()?->getKey() === (int) $user->getKey()) {
            $message = 'You cannot delete the account you are currently signed in with.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return back()->withErrors([
                'user' => $message,
            ]);
        }

        $screen = $this->screenForRole($user->role);
        $name = $user->name;
        $user->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$name} was deleted successfully.",
            ]);
        }

        return redirect()
            ->route(match ($screen) {
                'merchants' => 'admin.merchants.index',
                'customers', 'customer-details', 'purchase-history' => 'admin.customers.index',
                default => 'admin.users.index',
            })
            ->with('status', "{$name} was deleted successfully.");
    }

    private function screen(Request $request, string $screen, string $title): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(AdminDashboardData::accountsIndex($screen));
        }

        return $this->page($screen, $title);
    }

    private function page(string $screen, string $title): View
    {
        return view('backend.users.create', [
            'title' => $title,
            'context' => [
                'app' => 'backend-users',
                'screen' => $screen,
                'role_scope' => 'admin',
                'endpoint' => route(match ($screen) {
                'merchants' => 'admin.merchants.index',
                'customers' => 'admin.customers.index',
                'customer-details' => 'admin.customers.details',
                'purchase-history' => 'admin.customers.purchase-history',
                default => 'admin.users.index',
            }),
                'currentUserId' => request()->user()?->getKey(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            ...StorefrontData::user($user),
            'role' => $user->role,
        ];
    }

    private function screenForRole(?string $role): string
    {
        return match ($role) {
            'merchant' => 'merchants',
            'customer' => 'customers',
            default => 'users',
        };
    }
}
