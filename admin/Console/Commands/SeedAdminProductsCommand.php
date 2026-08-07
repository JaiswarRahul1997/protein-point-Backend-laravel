<?php

namespace Admin\Console\Commands;

use Database\Seeders\AdminProductSeeder;
use Illuminate\Console\Command;

class SeedAdminProductsCommand extends Command
{
    protected $signature = 'admin:seed-products';

    protected $description = 'Seed 10 dummy products for each product type (50 total)';

    public function handle(): int
    {
        $this->call(AdminProductSeeder::class);
        $this->info('Seeded 10 dummy products for each type: simple, configurable, bundle, grouped, virtual.');

        return self::SUCCESS;
    }
}
