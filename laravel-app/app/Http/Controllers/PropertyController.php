<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Property::with(['landlord', 'units'])
            ->when($this->isTenant($user), fn($q) => $q->whereHas('units.tenant', fn($tenant) => $tenant->where('user_id', $user->id)))
            ->when($user?->role?->name === 'LANDLORD', fn($q) => $q->where('landlord_id', $user->id))
            ->when($request->search, fn($q) => $q->where(function ($searchQuery) use ($request) {
                $searchQuery->where('name', 'like', "%{$request->search}%")
                    ->orWhere('city', 'like', "%{$request->search}%");
            }));
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->requireManager($request->user());
        $data = $request->validate([
            'landlord_id' => 'required|uuid|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'cover_image_url' => 'nullable|url',
            'address_line' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);
        if ($request->user()?->role?->name === 'LANDLORD') $data['landlord_id'] = $request->user()->id;

        // Handle file upload
        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->storePropertyImage($request->file('cover_image'));
        }
        unset($data['cover_image']);

        $property = Property::create($data);
        return response()->json($property->load('landlord'), 201);
    }

    public function show(Property $property)
    {
        $user = request()->user();
        abort_if($this->isTenant($user) && !$property->units()->whereHas('tenant', fn($tenant) => $tenant->where('user_id', $user->id))->exists(), 403);
        abort_if($user?->role?->name === 'LANDLORD' && $property->landlord_id !== $user->id, 403);

        return response()->json($property->load(['landlord', 'units']));
    }

    public function update(Request $request, Property $property)
    {
        $this->requirePropertyManager($request->user(), $property);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'cover_image_url' => 'nullable|url',
            'address_line' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        // Handle file upload - delete old image if new one is uploaded
        if ($request->hasFile('cover_image')) {
            if ($property->cover_image_url) {
                $this->deletePropertyImage($property->cover_image_url);
            }
            $data['cover_image_url'] = $this->storePropertyImage($request->file('cover_image'));
        }
        unset($data['cover_image']);

        $property->update($data);
        return response()->json($property->load('landlord'));
    }

    public function destroy(Property $property)
    {
        $this->requirePropertyManager(request()->user(), $property);
        abort_if($property->units()->exists(), 422, 'Remove or archive the property units before deleting this property.');
        // Clean up image when property is deleted
        if ($property->cover_image_url) {
            $this->deletePropertyImage($property->cover_image_url);
        }
        $property->delete();
        return response()->json(null, 204);
    }

    /**
     * Store a property image and return its URL
     */
    private function storePropertyImage($file)
    {
        $path = Storage::disk('public')->put('properties', $file);
        return asset('storage/' . $path);
    }

    /**
     * Delete a property image from storage
     */
    private function deletePropertyImage($imageUrl)
    {
        // Extract path from URL
        if (strpos($imageUrl, 'storage/') !== false) {
            $path = str_replace(asset('storage/'), '', $imageUrl);
            Storage::disk('public')->delete($path);
        }
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }
}
