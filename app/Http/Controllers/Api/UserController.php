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

        if ($fields) {

            $fields = explode(',', $fields);

            $users = $users->map(function ($user) use ($fields) {

                $data = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'gender' => $user->detail->gender ?? null,
                    'city' => $user->location->city ?? null,
                    'country' => $user->location->country ?? null,
                ];

                return collect($data)->only($fields);
            });
        }

        return response()->json($users);
    }
}
