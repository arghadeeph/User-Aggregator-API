<?php

namespace App\Console\Commands;

use App\Services\RandomUserService;
use Illuminate\Console\Command;

class FetchRandomUsersLegacy extends Command
{
    protected $signature = 'app:fetch-random-users';

    protected $description = 'Legacy command alias for users:fetch-random';

    public function __construct(private RandomUserService $randomUserService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->randomUserService->fetchAndStoreUsers();

        $this->info('Users fetched successfully.');
        $this->warn('This command is deprecated. Please use: php artisan users:fetch-random');

        return self::SUCCESS;
    }
}
