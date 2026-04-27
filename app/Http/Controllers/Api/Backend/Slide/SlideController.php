<?php

namespace App\Http\Controllers\Api\Backend\Slide;

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

        $currentImagePath = $slide->exists ? $slide->image_path : null;
        $newImagePath = $currentImagePath;

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('slides', 'public');
        }

        $slide->fill([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'image_path' => $newImagePath,
            'link' => $validated['link'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $slide->save();

        if ($currentImagePath && $currentImagePath !== $newImagePath) {
            Storage::disk('public')->delete($currentImagePath);
        }

        return $slide;
    }

    private function rules(?Slide $slide = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => [$slide ? 'nullable' : 'required', 'image', 'max:4096'],
            'link' => ['nullable', 'string', 'max:500', 'url'],
            'is_active' => ['nullable', 'boolean'],
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
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'image_url' => $slide->image_path ? Storage::disk('public')->url($slide->image_path) : null,
            'image_path' => $slide->image_path,
            'link' => $slide->link,
            'is_active' => $slide->is_active,
        ];
    }
}