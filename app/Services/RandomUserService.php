<?php

namespace App\Services;

use App\Models\Location;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RandomUserService
{
    public function fetchAndStoreUsers(): void
    {
        // We make 5 separate API calls
        for ($i = 0; $i < 5; $i++) {

            try {
                $response = Http::get('https://randomuser.me/api/');
            } catch (ConnectionException $exception) {
                continue; // skip if API host is unreachable
            }

            Log::info("Api Call Response: ".json_encode($response->json()));

            if (! $response->successful()) {
                continue; // skip if API fails
            }

            $results = $response->json('results');

            if (! is_array($results) || ! isset($results[0])) {
                continue;
            }

            $data = $results[0];

             Log::info("Get user Data: ".json_encode($data));

            if (
                ! isset(
                    $data['name']['first'],
                    $data['name']['last'],
                    $data['email'],
                    $data['gender'],
                    $data['location']['city'],
                    $data['location']['country']
                )
            ) {
                continue;
            }

            $name = $data['name']['first'].' '.$data['name']['last'];
            $email = $data['email'];
            $gender = $data['gender'];
            $city = $data['location']['city'];
            $country = $data['location']['country'];

            // Avoid duplicate email
            if (User::where('email', $email)->exists()) {
                continue;
            }

             Log::info("Storing user: $name, $email, $gender, $city, $country");
             
            // Store user
            $user = User::create([
                'name' => $name,
                'email' => $email,
            ]);

            // Store user details
            UserDetail::create([
                'user_id' => $user->id,
                'gender' => $gender,
            ]);

            // Store location
            Location::create([
                'user_id' => $user->id,
                'city' => $city,
                'country' => $country,
            ]);
        }
    }
}
