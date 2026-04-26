<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SlideController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $slide = $this->persistSlide($request, $validated);

        return response()->json([
            'message' => "{$slide->title} slide was created successfully.",
            'slide' => $this->editableSlide($slide),
        ], 201);
    }

    public function update(Request $request, Slide $slide): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $slide = $this->persistSlide($request, $validated, $slide);

        return response()->json([
            'message' => "{$slide->title} slide was updated successfully.",
            'slide' => $this->editableSlide($slide),
        ]);
    }

    public function destroy(Slide $slide): JsonResponse
    {
        $imagePath = $slide->resolvedImagePath();
        $title = $slide->title;

        $slide->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        Storage::disk('public')->deleteDirectory("slides/{$slide->id}");

        return response()->json([
            'message' => "{$title} slide was deleted successfully.",
        ]);
    }

    private function persistSlide(Request $request, array $validated, ?Slide $slide = null): Slide
    {
        $slide ??= new Slide();

        $existingImagePath = $slide->resolvedImagePath();
        $removeExistingImage = filter_var($validated['remove_existing_image'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$slide->image_path && $existingImagePath) {
            $slide->image_path = $existingImagePath;
        }

        $slide->fill([
            'category_id' => $validated['category_id'] ?? null,
            'eyebrow' => $this->nullableString($validated['eyebrow'] ?? null),
            'title' => $validated['title'],
            'highlight' => $this->nullableString($validated['highlight'] ?? null),
            'description' => $this->nullableString($validated['description'] ?? null),
            'button_text' => $this->nullableString($validated['button_text'] ?? null),
            'button_url' => $this->nullableString($validated['button_url'] ?? null),
            'badge_text' => $this->nullableString($validated['badge_text'] ?? null),
            'is_active' => filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN),
            'sort_order' => (int) $validated['sort_order'],
        ]);

        if ($removeExistingImage) {
            $slide->image_path = null;
        }

        $slide->save();

        $uploadedImage = $request->file('image');

        if ($uploadedImage) {
            Storage::disk('public')->deleteDirectory("slides/{$slide->id}");

            if ($existingImagePath) {
                Storage::disk('public')->delete($existingImagePath);
            }

            $newPath = $uploadedImage->store("slides/{$slide->id}", 'public');
            $slide->image_path = $newPath;
            $slide->save();
        } elseif ($removeExistingImage && $existingImagePath) {
            Storage::disk('public')->delete($existingImagePath);
            Storage::disk('public')->deleteDirectory("slides/{$slide->id}");
        }

        return $slide->fresh('category');
    }

    private function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'eyebrow' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'highlight' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'badge_text' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_existing_image' => ['nullable', 'boolean'],
            'is_active' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    private function editableSlide(Slide $slide): array
    {
        return [
            'id' => $slide->id,
            'category_id' => $slide->category_id ? (string) $slide->category_id : '',
            'category' => $slide->category?->name ?? 'All categories',
            'eyebrow' => $slide->eyebrow ?? '',
            'title' => $slide->title,
            'highlight' => $slide->highlight ?? '',
            'description' => $slide->description ?? '',
            'button_text' => $slide->button_text ?? '',
            'button_url' => $slide->button_url ?? '',
            'badge_text' => $slide->badge_text ?? '',
            'is_active' => (bool) $slide->is_active,
            'sort_order' => (string) $slide->sort_order,
            'existing_image_url' => $slide->resolvedImageUrl(),
            'existing_image_name' => $slide->resolvedImageName(),
            'remove_existing_image' => false,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }
}
