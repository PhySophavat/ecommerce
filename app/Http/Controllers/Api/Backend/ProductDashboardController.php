<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\AdminDashboardData;

class ProductDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(AdminDashboardData::productsIndex((string) $request->query('screen', 'products')));
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'images', 'variants']);

        return response()->json([
            'product' => $this->editableProduct($product),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $product = $this->persistProduct($request, $validated);

        return response()->json([
            'message' => "{$product->name} was created successfully.",
            'product_id' => $product->id,
            'product' => $this->editableProduct($product),
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate($this->rules($product));
        $product = $this->persistProduct($request, $validated, $product);

        return response()->json([
            'message' => "{$product->name} was updated successfully.",
            'product_id' => $product->id,
            'product' => $this->editableProduct($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $imagePaths = $product->images()
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
        $variantImagePaths = $product->variants()
            ->pluck('image_path')
            ->filter()
            ->values()
            ->all();

        DB::transaction(function () use ($product): void {
            $product->delete();
        });

        $pathsToDelete = array_values(array_unique([
            ...$imagePaths,
            ...$variantImagePaths,
        ]));

        if ($pathsToDelete !== []) {
            Storage::disk('public')->delete($pathsToDelete);
        }

        return response()->json([
            'message' => "{$product->name} was deleted successfully.",
        ]);
    }

    private function persistProduct(Request $request, array $validated, ?Product $product = null): Product
    {
        $product ??= new Product();

        $currentName = $product->exists ? $product->name : null;
        $removedImagePaths = [];
        $removedVariantImagePaths = [];

        DB::transaction(function () use ($request, $validated, $product, $currentName, &$removedImagePaths, &$removedVariantImagePaths): void {
            $originalPrice = (float) $validated['price'];
            $discountPrice = array_key_exists('discount_price', $validated) && $validated['discount_price'] !== null
                ? (float) $validated['discount_price']
                : null;

            $product->fill([
                'category_id' => $validated['category_id'],
                'type' => $validated['type'],
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'tagline' => Str::limit(strip_tags($validated['description']), 90, ''),
                'description' => $validated['description'],
                'price' => $discountPrice ?? $originalPrice,
                'compare_at_price' => $discountPrice ? $originalPrice : null,
                'inventory' => $validated['stock_quantity'],
                'status' => $validated['status'],
            ]);

            if (! $product->exists) {
                $product->theme = 'cobalt';
                $product->is_featured = false;
                $product->rating = 4.80;
                $product->reviews_count = 0;
            }

            if (! $product->exists || $currentName !== $validated['name']) {
                $product->slug = $this->uniqueSlug($validated['name'], $product);
            }

            $product->save();

            $imageIdsToRemove = collect($validated['removed_image_ids'] ?? [])
                ->map(fn (mixed $id): int => (int) $id)
                ->filter()
                ->values();

            if ($imageIdsToRemove->isNotEmpty()) {
                $imagesToRemove = $product->images()
                    ->whereIn('id', $imageIdsToRemove)
                    ->get();

                $removedImagePaths = $imagesToRemove
                    ->pluck('path')
                    ->filter()
                    ->values()
                    ->all();

                ProductImage::query()
                    ->whereIn('id', $imageIdsToRemove)
                    ->delete();
            }

            $allowedVariantImagePaths = $product->variants()
                ->pluck('image_path')
                ->filter()
                ->values()
                ->all();

            $removedVariantImagePaths = $allowedVariantImagePaths;

            $product->variants()->delete();

            collect($validated['variants'] ?? [])
                ->values()
                ->each(function (array $variant, int $index) use ($product, $request, $allowedVariantImagePaths, &$removedVariantImagePaths): void {
                    $attributes = collect($variant['attributes'] ?? [])
                        ->map(fn (array $attribute): array => [
                            'name' => trim((string) ($attribute['name'] ?? '')),
                            'value' => trim((string) ($attribute['value'] ?? '')),
                        ])
                        ->filter(fn (array $attribute): bool => $attribute['name'] !== '' && $attribute['value'] !== '')
                        ->values();

                    $existingImagePath = $variant['existing_image_path'] ?? null;
                    $existingImagePath = in_array($existingImagePath, $allowedVariantImagePaths, true)
                        ? $existingImagePath
                        : null;

                    $uploadedImage = data_get($request->file('variants', []), "{$index}.image");
                    $removeExistingImage = filter_var($variant['remove_existing_image'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $imagePath = $removeExistingImage ? null : $existingImagePath;

                    if ($uploadedImage) {
                        $imagePath = $uploadedImage->store("products/{$product->id}/variants", 'public');
                    }

                    if ($imagePath !== null) {
                        $removedVariantImagePaths = array_values(array_diff($removedVariantImagePaths, [$imagePath]));
                    }

                    $product->variants()->create([
                        'label' => $variant['label'],
                        'size' => $this->variantFallbackValue($attributes, 'Size', 0),
                        'color' => $this->variantFallbackValue($attributes, 'Color', 1),
                        'option_values' => $attributes->all(),
                        'sku' => $this->nullableString($variant['variant_sku'] ?? null),
                        'image_path' => $imagePath,
                        'price' => $variant['price'],
                        'stock' => $variant['stock'],
                    ]);
                });

            $startingSortOrder = (int) ($product->images()->max('sort_order') ?? 0);

            collect($request->file('images', []))
                ->values()
                ->each(function ($file, int $index) use ($product, $startingSortOrder): void {
                    $path = $file->store("products/{$product->id}", 'public');

                    $product->images()->create([
                        'path' => $path,
                        'sort_order' => $startingSortOrder + $index + 1,
                    ]);
                });
        });

        if ($removedImagePaths !== []) {
            Storage::disk('public')->delete($removedImagePaths);
        }

        if ($removedVariantImagePaths !== []) {
            Storage::disk('public')->delete($removedVariantImagePaths);
        }

        return $product->fresh(['images', 'variants', 'category']);
    }

    private function rules(?Product $product = null): array
    {
        $skuRule = Rule::unique('products', 'sku');

        if ($product) {
            $skuRule = $skuRule->ignore($product->id);
        }

        $seenVariantSkus = [];
        $variantSkuRule = function (string $attribute, mixed $value, \Closure $fail) use ($product, &$seenVariantSkus): void {
            $sku = trim((string) $value);

            if ($sku === '') {
                return;
            }

            if (in_array($sku, $seenVariantSkus, true)) {
                $fail('Variant SKUs must be unique.');

                return;
            }

            $seenVariantSkus[] = $sku;

            $exists = ProductVariant::query()
                ->where('sku', $sku)
                ->when($product, fn ($query) => $query->where('product_id', '!=', $product->id))
                ->exists();

            if ($exists) {
                $fail('The variant SKU has already been taken.');
            }
        };

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(['men', 'women'])],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'sku' => ['required', 'string', 'max:255', $skuRule],
            'status' => ['required', Rule::in(['active', 'draft', 'scheduled'])],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'variants' => ['nullable', 'array'],
            'variants.*.label' => ['required', 'string', 'max:255'],
            'variants.*.attributes' => ['required', 'array', 'min:1'],
            'variants.*.attributes.*.name' => ['required', 'string', 'max:50'],
            'variants.*.attributes.*.value' => ['required', 'string', 'max:100'],
            'variants.*.variant_sku' => ['nullable', 'string', 'max:255', $variantSkuRule],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:4096'],
            'variants.*.existing_image_path' => ['nullable', 'string', 'max:255'],
            'variants.*.remove_existing_image' => ['nullable', 'boolean'],
            'removed_image_ids' => ['nullable', 'array'],
        ];

        if ($product) {
            $rules['removed_image_ids.*'] = [
                'integer',
                Rule::exists('product_images', 'id')->where(fn ($query) => $query->where('product_id', $product->id)),
            ];
        }

        return $rules;
    }

    private function editableProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'category_slug' => $product->category?->slug,
            'type' => $product->type,
            'description' => $product->description,
            'price' => $product->compare_at_price ? (string) $product->compare_at_price : (string) $product->price,
            'discount_price' => $product->compare_at_price ? (string) $product->price : '',
            'stock_quantity' => (string) $product->inventory,
            'sku' => $product->sku,
            'status' => $product->status,
            'existing_images' => $product->images
                ->sortBy('sort_order')
                ->values()
                ->map(fn (ProductImage $image): array => [
                    'id' => $image->id,
                    'name' => basename($image->path),
                    'url' => Storage::disk('public')->url($image->path),
                ])
                ->all(),
            'variants' => $product->variants
                ->values()
                ->map(fn (ProductVariant $variant): array => [
                    'label' => $variant->label ?: $this->variantLabel($variant),
                    'attributes' => $this->variantAttributes($variant),
                    'variant_sku' => $variant->sku ?? '',
                    'price' => (string) $variant->price,
                    'stock' => (string) $variant->stock,
                    'existing_image_url' => $variant->image_path ? Storage::disk('public')->url($variant->image_path) : '',
                    'existing_image_path' => $variant->image_path ?? '',
                    'remove_existing_image' => false,
                ])
                ->all(),
            'variant_groups' => $this->variantGroups($product->variants),
            'variant_group_source' => $product->category?->slug,
        ];
    }

    /**
     * @param  Collection<int, array{name: string, value: string}>  $attributes
     */
    private function variantFallbackValue(Collection $attributes, string $preferredName, int $fallbackIndex): string
    {
        $preferred = $attributes->first(fn (array $attribute): bool => Str::lower($attribute['name']) === Str::lower($preferredName));

        if ($preferred) {
            return $preferred['value'];
        }

        return $attributes[$fallbackIndex]['value'] ?? 'Default';
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }

    /**
     * @return array<int, array{name: string, value: string}>
     */
    private function variantAttributes(ProductVariant $variant): array
    {
        $optionValues = collect($variant->option_values ?? [])
            ->map(fn (array $attribute): array => [
                'name' => trim((string) ($attribute['name'] ?? '')),
                'value' => trim((string) ($attribute['value'] ?? '')),
            ])
            ->filter(fn (array $attribute): bool => $attribute['name'] !== '' && $attribute['value'] !== '')
            ->values();

        if ($optionValues->isNotEmpty()) {
            return $optionValues->all();
        }

        return collect([
            ['name' => 'Size', 'value' => $variant->size],
            ['name' => 'Color', 'value' => $variant->color],
        ])
            ->filter(fn (array $attribute): bool => trim((string) $attribute['value']) !== '')
            ->values()
            ->all();
    }

    private function variantLabel(ProductVariant $variant): string
    {
        return collect($this->variantAttributes($variant))
            ->pluck('value')
            ->implode(' / ');
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return array<int, array{name: string, options_text: string}>
     */
    private function variantGroups(Collection $variants): array
    {
        $groupMap = [];

        foreach ($variants as $variant) {
            foreach ($this->variantAttributes($variant) as $attribute) {
                if (! isset($groupMap[$attribute['name']])) {
                    $groupMap[$attribute['name']] = [];
                }

                if (! in_array($attribute['value'], $groupMap[$attribute['name']], true)) {
                    $groupMap[$attribute['name']][] = $attribute['value'];
                }
            }
        }

        return collect($groupMap)
            ->map(fn (array $options, string $name): array => [
                'name' => $name,
                'options_text' => implode(', ', $options),
            ])
            ->values()
            ->all();
    }

    private function uniqueSlug(string $name, ?Product $product = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($product?->exists, fn ($query) => $query->whereKeyNot($product->id))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
