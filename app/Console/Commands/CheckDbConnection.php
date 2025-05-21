<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDbConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-db-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check database connection availability';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::connection()->getPdo();
            $this->info('Database connection established');
            return 0; // Success exit code
        } catch (\Exception $e) {
            $this->error('Database connection failed: '.$e->getMessage());
            return 1; // Error exit code
        }
    }
}
