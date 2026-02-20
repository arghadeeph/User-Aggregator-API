<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['detail', 'location']);

        // Filter by gender
        if ($request->filled('gender')) {
            $query->whereHas('detail', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        // Filter by city
        if ($request->filled('city')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('city', $request->city);
            });
        }

        // Filter by country
        if ($request->filled('country')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('country', $request->country);
            });
        }

        // Limit number of records
        $limit = $request->get('limit', 10);

        $users = $query->limit($limit)->get();

        $fields = $request->get('fields');
        $allowedFields = ['name', 'email', 'gender', 'city', 'country'];

        if ($fields) {
            $fields = array_values(array_intersect(
                $allowedFields,
                array_map('trim', explode(',', $fields))
            ));

            if (empty($fields)) {
                $fields = $allowedFields;
            }
        } else {
            $fields = $allowedFields;
        }

        $data = $users->map(function ($user) use ($fields) {
            $item = [
                'name' => $user->name,
                'email' => $user->email,
                'gender' => $user->detail->gender ?? null,
                'city' => $user->location->city ?? null,
                'country' => $user->location->country ?? null,
            ];

            return collect($item)->only($fields)->all();
        })->values();

        return response()->json([
            'success' => true,
            'message' => $data->isEmpty() ? 'No users found.' : 'Users fetched successfully.',
            'count' => $data->count(),
            'data' => $data,
        ]);
    }
}
