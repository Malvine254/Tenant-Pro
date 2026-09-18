<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MarketplaceUnitDetails
{
    public const AMENITIES = ['Parking', 'Reliable water supply', 'Internet ready', 'Balcony', 'Lift', 'Step-free access', 'Security staff', 'Pets considered', 'Backup power', 'Laundry area'];

    public const INTERIOR_AREAS = [
        'living_room' => 'Living room',
        'kitchen' => 'Kitchen',
        'bedroom' => 'Bedroom',
        'bathroom' => 'Bathroom',
        'dining' => 'Dining area',
        'balcony' => 'Balcony',
        'entrance' => 'Entrance',
        'other' => 'Other',
    ];

    public static function rules(): array
    {
        return [
            'bathrooms' => 'nullable|integer|min:0|max:20',
            'deposit_amount' => 'nullable|numeric|min:0|max:9999999999',
            'service_charge' => 'nullable|numeric|min:0|max:9999999999',
            'other_move_in_cost' => 'nullable|numeric|min:0|max:9999999999',
            'other_move_in_label' => 'nullable|string|max:100',
            'amenities' => 'nullable|array|max:10',
            'amenities.*' => ['string', Rule::in(self::AMENITIES)],
            'available_from' => 'nullable|date_format:Y-m-d',
            'confirm_availability' => 'nullable|boolean',
            'photos' => 'nullable|array|max:12',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_photos' => 'nullable|array|max:12',
            'remove_photos.*' => 'integer|min:0|max:100',
            'interior_photos' => 'nullable|array:'.implode(',', array_keys(self::INTERIOR_AREAS)),
            'interior_photos.*' => 'nullable|array|max:6',
            'interior_photos.*.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_interior_photos' => 'nullable|array|max:24',
            'remove_interior_photos.*' => 'integer|min:0|max:100',
        ];
    }

    public static function fields(array $data, ?Unit $unit = null): array
    {
        if (($data['other_move_in_cost'] ?? 0) > 0 && blank($data['other_move_in_label'] ?? null)) {
            throw ValidationException::withMessages(['other_move_in_label' => 'Describe the additional one-time charge.']);
        }
        return array_merge(collect($data)->only(['bathrooms', 'deposit_amount', 'service_charge', 'other_move_in_cost', 'other_move_in_label', 'available_from'])->all(), [
            'amenities' => array_values(array_unique($data['amenities'] ?? [])),
            'availability_confirmed_at' => ($data['status'] ?? '') !== 'AVAILABLE' ? null
                : (! empty($data['confirm_availability']) ? now() : $unit?->availability_confirmed_at),
        ]);
    }

    public static function updateWithPhotos(Unit $unit, Request $request, array $fields): void
    {
        $removePhotoIndexes = array_map('intval', $request->input('remove_photos', []));
        $removeInteriorIndexes = array_map('intval', $request->input('remove_interior_photos', []));
        $currentPhotos = collect($unit->image_urls ?? []);
        $currentGallery = collect($unit->interior_gallery ?? []);
        $retained = $currentPhotos->reject(fn ($url, $index) => in_array($index, $removePhotoIndexes, true))->values()->all();
        $uploads = $request->file('photos', []);
        if (count($retained) + count($uploads) > 12) {
            throw ValidationException::withMessages(['photos' => 'Keep up to 12 photos per unit. Remove older photos before adding more.']);
        }
        $gallery = $currentGallery
            ->reject(fn ($photo, $index) => in_array($index, $removeInteriorIndexes, true))
            ->values()
            ->all();
        $interiorUploads = $request->file('interior_photos', []);
        $interiorUploadCount = collect($interiorUploads)->sum(
            fn ($files) => count(is_array($files) ? $files : [$files])
        );
        if (count($gallery) + $interiorUploadCount > 24) {
            throw ValidationException::withMessages(['interior_photos' => 'Keep up to 24 labeled interior photos per unit.']);
        }

        $newPaths = [];
        try {
            foreach ($uploads as $file) {
                $path = Storage::disk('public')->putFile('unit-photos', $file);
                if (! $path) throw new \RuntimeException('The photo could not be stored.');
                $newPaths[] = $path;
                $retained[] = '/storage/'.$path;
            }
            foreach ($interiorUploads as $area => $files) {
                foreach (is_array($files) ? $files : [$files] as $file) {
                    $path = Storage::disk('public')->putFile('unit-interiors', $file);
                    if (! $path) throw new \RuntimeException('The interior photo could not be stored.');
                    $newPaths[] = $path;
                    $gallery[] = [
                        'area' => $area,
                        'label' => self::INTERIOR_AREAS[$area],
                        'url' => '/storage/'.$path,
                    ];
                }
            }
            $unit->update(array_merge($fields, [
                'image_urls' => $retained,
                'interior_gallery' => $gallery,
            ]));
            $removedUrls = $currentPhotos->filter(fn ($url, $index) => in_array($index, $removePhotoIndexes, true))
                ->merge($currentGallery->filter(fn ($photo, $index) => in_array($index, $removeInteriorIndexes, true))->pluck('url'));
            $removedPaths = $removedUrls
                ->filter(fn ($url) => is_string($url) && Str::contains($url, '/storage/'))
                ->map(fn ($url) => Str::after($url, '/storage/'))
                ->all();
            Storage::disk('public')->delete($removedPaths);
        } catch (Throwable $error) {
            Storage::disk('public')->delete($newPaths);
            throw $error;
        }
    }

    /** Copies final media to sibling units without sharing files between units. */
    public static function copyMediaToUnits(Unit $source, iterable $units): int
    {
        $sourcePhotos = array_values($source->image_urls ?? []);
        $sourceGallery = array_values($source->interior_gallery ?? []);
        if ($sourcePhotos === [] && $sourceGallery === []) {
            return 0;
        }

        $copied = 0;
        foreach ($units as $unit) {
            if ($unit->id === $source->id) {
                continue;
            }

            $newPaths = [];
            try {
                $photos = array_map(
                    fn ($url) => self::duplicateMediaUrl($url, 'unit-photos', $newPaths),
                    $sourcePhotos
                );
                $gallery = array_map(function ($photo) use (&$newPaths) {
                    $photo['url'] = self::duplicateMediaUrl($photo['url'] ?? null, 'unit-interiors', $newPaths);

                    return $photo;
                }, $sourceGallery);

                $oldPaths = self::storagePaths(array_merge(
                    $unit->image_urls ?? [],
                    collect($unit->interior_gallery ?? [])->pluck('url')->all()
                ));
                $unit->update(['image_urls' => $photos, 'interior_gallery' => $gallery]);
                Storage::disk('public')->delete($oldPaths);
                $copied++;
            } catch (Throwable $error) {
                Storage::disk('public')->delete($newPaths);
                throw $error;
            }
        }

        return $copied;
    }

    private static function duplicateMediaUrl(?string $url, string $directory, array &$newPaths): ?string
    {
        if (blank($url) || ! Str::contains($url, '/storage/')) {
            return $url;
        }

        $sourcePath = Str::after($url, '/storage/');
        if (! Storage::disk('public')->exists($sourcePath)) {
            throw new \RuntimeException('The source media file is missing.');
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $targetPath = $directory.'/'.Str::uuid().'.'.$extension;
        if (! Storage::disk('public')->copy($sourcePath, $targetPath)) {
            throw new \RuntimeException('The media file could not be copied.');
        }

        $newPaths[] = $targetPath;

        return '/storage/'.$targetPath;
    }

    private static function storagePaths(array $urls): array
    {
        return collect($urls)
            ->filter(fn ($url) => is_string($url) && Str::contains($url, '/storage/'))
            ->map(fn ($url) => Str::after($url, '/storage/'))
            ->all();
    }
}
