<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            FaqSeeder::class,
            DressingItemSeeder::class,
            LegalPageSeeder::class,
            SeoMetaSeeder::class,
            EditionPieceSeeder::class,
            CommerceSettingSeeder::class,
            SiteSettingSeeder::class,
            JournalArticleSeeder::class,
            ClubSeeder::class,
            CommunityDemoSeeder::class,
        ]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
