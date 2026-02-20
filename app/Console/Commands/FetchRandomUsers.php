<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class FetchRandomUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-random-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch 5 users from randomuser.me and store them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $savedUsersCount = 0;

        for ($index = 0; $index < 5; $index++) {
            try {
                // Requirement says 5 separate API calls every execution.
                $response = Http::timeout(15)->get('https://randomuser.me/api/');

                if ($response->failed()) {
                    $this->warn('Request failed for call '.($index + 1));
                    continue;
                }

                $result = $response->json('results.0');

                if (! is_array($result)) {
                    $this->warn('Invalid response format for call '.($index + 1));
                    continue;
                }

                $fullName = trim(
                    (string) data_get($result, 'name.first', '').' '.
                    (string) data_get($result, 'name.last', '')
                );
                $email = (string) data_get($result, 'email', '');
                $gender = (string) data_get($result, 'gender', '');
                $city = (string) data_get($result, 'location.city', '');
                $country = (string) data_get($result, 'location.country', '');

                if ($fullName === '' || $email === '') {
                    $this->warn('Missing required user fields on call '.($index + 1));
                    continue;
                }

                DB::transaction(function () use ($fullName, $email, $gender, $city, $country): void {
                    $user = User::updateOrCreate(
                        ['email' => $email],
                        ['name' => $fullName]
                    );

                    UserDetail::updateOrCreate(
                        ['user_id' => $user->id],
                        ['gender' => $gender]
                    );

                    Location::updateOrCreate(
                        ['user_id' => $user->id],
                        ['city' => $city, 'country' => $country]
                    );
                });

                $savedUsersCount++;
            } catch (Throwable $exception) {
                $this->warn('Could not save call '.($index + 1).': '.$exception->getMessage());
            }
        }

        $this->info("Done. Saved or updated {$savedUsersCount} users.");

        return self::SUCCESS;
    }
}
