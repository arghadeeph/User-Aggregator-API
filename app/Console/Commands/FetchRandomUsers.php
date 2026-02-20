<?php

namespace App\Console\Commands;

use App\Services\RandomUserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchRandomUsers extends Command
{
    public function __construct(private RandomUserService $randomUserService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fetch-random';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch 5 random users from external API and store them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Call service class to fetch and store users
        $this->randomUserService->fetchAndStoreUsers();
        Log::info("Fetched users successfully.");
        return self::SUCCESS;
    }
}
