<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MarketplaceUnitDetails
{
    public const AMENITIES = ['Parking', 'Reliable water supply', 'Internet ready', 'Balcony', 'Lift', 'Step-free access', 'Security staff', 'Pets considered', 'Backup power', 'Laundry area'];

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
        $retained = collect($unit->image_urls ?? [])->reject(fn ($url, $index) => in_array($index, array_map('intval', $request->input('remove_photos', [])), true))->values()->all();
        $uploads = $request->file('photos', []);
        if (count($retained) + count($uploads) > 12) {
            throw ValidationException::withMessages(['photos' => 'Keep up to 12 photos per unit. Remove older photos before adding more.']);
        }
        $newPaths = [];
        try {
            foreach ($uploads as $file) {
                $path = Storage::disk('public')->putFile('unit-photos', $file);
                if (! $path) throw new \RuntimeException('The photo could not be stored.');
                $newPaths[] = $path;
                $retained[] = '/storage/'.$path;
            }
            $unit->update(array_merge($fields, ['image_urls' => $retained]));
        } catch (Throwable $error) {
            Storage::disk('public')->delete($newPaths);
            throw $error;
        }
    }
}
