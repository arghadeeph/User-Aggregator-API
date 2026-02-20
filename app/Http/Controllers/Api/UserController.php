<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gender' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'fields' => ['nullable', 'string'],
        ]);

        $query = User::query()->with(['detail', 'location']);

        if (isset($validated['gender'])) {
            $query->whereHas('detail', function ($detailQuery) use ($validated): void {
                $detailQuery->where('gender', $validated['gender']);
            });
        }

        if (isset($validated['city'])) {
            $query->whereHas('location', function ($locationQuery) use ($validated): void {
                $locationQuery->where('city', $validated['city']);
            });
        }

        if (isset($validated['country'])) {
            $query->whereHas('location', function ($locationQuery) use ($validated): void {
                $locationQuery->where('country', $validated['country']);
            });
        }

        $limit = $validated['limit'] ?? 10;
        $users = $query->limit($limit)->get();

        $allowedFields = ['name', 'email', 'gender', 'city', 'country'];
        $selectedFields = $allowedFields;

        // Optional enhancement: client can choose which fields are returned.
        if (isset($validated['fields'])) {
            $requestedFields = collect(explode(',', $validated['fields']))
                ->map(fn (string $field): string => trim($field))
                ->filter()
                ->values()
                ->all();

            $selectedFields = array_values(array_intersect($allowedFields, $requestedFields));

            if ($selectedFields === []) {
                $selectedFields = $allowedFields;
            }
        }

        $data = $users->map(function (User $user) use ($selectedFields): array {
            $userData = [
                'name' => $user->name,
                'email' => $user->email,
                'gender' => optional($user->detail)->gender,
                'city' => optional($user->location)->city,
                'country' => optional($user->location)->country,
            ];

            return collect($userData)->only($selectedFields)->all();
        });

        return response()->json([
            'count' => $data->count(),
            'data' => $data,
        ]);
    }
}
