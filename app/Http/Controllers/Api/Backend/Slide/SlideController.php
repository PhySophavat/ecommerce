<?php

namespace App\Http\Controllers\Api\Backend\Slide;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $validated = $request->validate($this->rules($slide));
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

        $currentImagePath = $slide->exists ? $slide->image_path : null;
        $newImagePath = $currentImagePath;
        $removeExistingImage = (bool) ($validated['remove_existing_image'] ?? false);

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('slides', 'public');
        } elseif ($removeExistingImage) {
            $newImagePath = null;
        }

        $slide->fill([
            'category_id' => $validated['category_id'] ?? null,
            'eyebrow' => $validated['eyebrow'] ?? null,
            'title' => $validated['title'],
            'highlight' => $validated['highlight'] ?? null,
            'description' => $validated['description'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'badge_text' => $validated['badge_text'] ?? null,
            'image_path' => $newImagePath,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 1,
        ]);

        $slide->save();

        if ($currentImagePath && $currentImagePath !== $newImagePath) {
            Storage::disk('public')->delete($currentImagePath);
        }

        return $slide->loadMissing('category');
    }

    private function rules(?Slide $slide = null): array
    {
        $rules = [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'eyebrow' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'highlight' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'badge_text' => ['nullable', 'string', 'max:100'],
            'image' => [$slide ? 'nullable' : 'required', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'remove_existing_image' => ['nullable', 'boolean'],
        ];

        if ($slide) {
            $rules['image'][] = 'sometimes';
        }

        return $rules;
    }

    private function editableSlide(Slide $slide): array
    {
        return [
            'id' => $slide->id,
            'category_id' => $slide->category_id ? (string) $slide->category_id : '',
            'category' => $slide->category?->name ?? 'All categories',
            'category_slug' => $slide->category?->slug,
            'eyebrow' => $slide->eyebrow ?? '',
            'title' => $slide->title,
            'highlight' => $slide->highlight ?? '',
            'description' => $slide->description ?? '',
            'button_text' => $slide->button_text ?? '',
            'button_url' => $slide->button_url ?? '',
            'badge_text' => $slide->badge_text ?? '',
            'image_url' => $slide->resolvedImageUrl(),
            'image_name' => $slide->resolvedImageName(),
            'is_active' => (bool) $slide->is_active,
            'sort_order' => (string) $slide->sort_order,
        ];
    }
}
