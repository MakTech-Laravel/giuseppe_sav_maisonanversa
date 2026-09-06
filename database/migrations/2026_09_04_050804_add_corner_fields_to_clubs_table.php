<?php

use App\Enums\ClubStatus;
use App\Enums\CornerPipelineStatus;
use App\Enums\SessionSport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->boolean('is_session_venue')->default(true)->after('is_partner')->index();
            $table->boolean('show_on_corner_page')->default(false)->after('is_session_venue')->index();
            $table->string('corner_pipeline_status')->nullable()->after('show_on_corner_page');
            $table->boolean('has_corner')->default(false)->after('corner_pipeline_status')->index();
            $table->boolean('corner_published')->default(false)->after('has_corner')->index();
            $table->string('corner_title')->nullable()->after('corner_published');
            $table->text('corner_body')->nullable()->after('corner_title');
            $table->string('corner_location')->nullable()->after('corner_body');
            $table->integer('sort_order')->default(0)->after('corner_location')->index();
        });

        $this->migratePartnerClubs();
        $this->migrateCommunityCourts();

        Schema::dropIfExists('partner_clubs');
        Schema::dropIfExists('community_courts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('partner_clubs', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('country');
            $table->string('status')->default('open');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('community_courts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('location')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn([
                'is_session_venue',
                'show_on_corner_page',
                'corner_pipeline_status',
                'has_corner',
                'corner_published',
                'corner_title',
                'corner_body',
                'corner_location',
                'sort_order',
            ]);
        });
    }

    private function migratePartnerClubs(): void
    {
        if (! Schema::hasTable('partner_clubs')) {
            return;
        }

        $now = now();

        foreach (DB::table('partner_clubs')->orderBy('id')->get() as $row) {
            $city = (string) $row->city;
            $country = $this->normalizeCountry((string) $row->country);
            $pipeline = CornerPipelineStatus::tryFrom((string) $row->status)?->value
                ?? CornerPipelineStatus::Open->value;

            $existing = DB::table('clubs')
                ->whereRaw('LOWER(city) = ?', [mb_strtolower($city)])
                ->where('status', '!=', ClubStatus::Merged->value)
                ->orderBy('id')
                ->first();

            if ($existing !== null) {
                DB::table('clubs')->where('id', $existing->id)->update([
                    'show_on_corner_page' => (bool) $row->is_published,
                    'corner_pipeline_status' => $pipeline,
                    'sort_order' => (int) $row->sort_order,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $slug = $this->uniqueSlug($city !== '' ? $city : 'club');

            DB::table('clubs')->insert([
                'name' => $city !== '' ? $city : 'Club',
                'slug' => $slug,
                'sports' => json_encode([SessionSport::Padel->value]),
                'street' => null,
                'postal_code' => null,
                'city' => $city !== '' ? $city : '—',
                'country' => $country,
                'lat' => null,
                'lng' => null,
                'website' => null,
                'phone' => null,
                'image_path' => null,
                'status' => ClubStatus::Approved->value,
                'is_partner' => false,
                'is_session_venue' => false,
                'show_on_corner_page' => (bool) $row->is_published,
                'corner_pipeline_status' => $pipeline,
                'has_corner' => false,
                'corner_published' => false,
                'corner_title' => null,
                'corner_body' => null,
                'corner_location' => null,
                'sort_order' => (int) $row->sort_order,
                'submitted_by_id' => null,
                'approved_by_id' => null,
                'approved_at' => $now,
                'merged_into_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('translations')
            ->where('translatable_type', 'App\\Models\\PartnerClub')
            ->delete();
    }

    private function migrateCommunityCourts(): void
    {
        if (! Schema::hasTable('community_courts')) {
            return;
        }

        $now = now();
        $columnMap = [
            'title' => 'corner_title',
            'body' => 'corner_body',
            'location' => 'corner_location',
        ];

        foreach (DB::table('community_courts')->orderBy('id')->get() as $row) {
            $title = (string) $row->title;
            $location = (string) ($row->location ?? '');
            $cityGuess = $this->cityFromLocation($location);

            $existing = DB::table('clubs')
                ->where(function ($query) use ($title, $cityGuess): void {
                    $query->whereRaw('LOWER(name) = ?', [mb_strtolower($title)]);

                    if ($cityGuess !== null) {
                        $query->orWhereRaw('LOWER(city) = ?', [mb_strtolower($cityGuess)]);
                    }
                })
                ->where('status', '!=', ClubStatus::Merged->value)
                ->orderBy('id')
                ->first();

            $clubId = $existing?->id;

            if ($clubId === null) {
                $slug = $this->uniqueSlug($title !== '' ? $title : 'club-corner');
                $clubId = DB::table('clubs')->insertGetId([
                    'name' => $title !== '' ? $title : 'Club Corner',
                    'slug' => $slug,
                    'sports' => json_encode([SessionSport::Padel->value]),
                    'street' => null,
                    'postal_code' => null,
                    'city' => $cityGuess ?? '—',
                    'country' => $this->countryFromLocation($location),
                    'lat' => $row->lat,
                    'lng' => $row->lng,
                    'website' => null,
                    'phone' => null,
                    'image_path' => null,
                    'status' => ClubStatus::Approved->value,
                    'is_partner' => false,
                    'is_session_venue' => false,
                    'show_on_corner_page' => false,
                    'corner_pipeline_status' => null,
                    'has_corner' => true,
                    'corner_published' => (bool) $row->is_published,
                    'corner_title' => $title !== '' ? $title : null,
                    'corner_body' => $row->body,
                    'corner_location' => $location !== '' ? $location : null,
                    'sort_order' => (int) $row->sort_order,
                    'submitted_by_id' => null,
                    'approved_by_id' => null,
                    'approved_at' => $now,
                    'merged_into_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $updates = [
                    'has_corner' => true,
                    'corner_published' => (bool) $row->is_published,
                    'corner_title' => $title !== '' ? $title : null,
                    'corner_body' => $row->body,
                    'corner_location' => $location !== '' ? $location : null,
                    'sort_order' => (int) $row->sort_order,
                    'updated_at' => $now,
                ];

                if ($row->lat !== null && $existing->lat === null) {
                    $updates['lat'] = $row->lat;
                }

                if ($row->lng !== null && $existing->lng === null) {
                    $updates['lng'] = $row->lng;
                }

                DB::table('clubs')->where('id', $clubId)->update($updates);
            }

            foreach ($columnMap as $oldColumn => $newColumn) {
                DB::table('translations')
                    ->where('translatable_type', 'App\\Models\\CommunityCourt')
                    ->where('translatable_id', $row->id)
                    ->where('column', $oldColumn)
                    ->update([
                        'translatable_type' => 'App\\Models\\Club',
                        'translatable_id' => $clubId,
                        'column' => $newColumn,
                    ]);
            }
        }

        DB::table('translations')
            ->where('translatable_type', 'App\\Models\\CommunityCourt')
            ->delete();
    }

    private function normalizeCountry(string $country): string
    {
        $normalized = mb_strtolower(trim($country));

        return match (true) {
            $normalized === '' => 'BE',
            in_array($normalized, ['be', 'belgië', 'belgie', 'belgium', 'belgique'], true) => 'BE',
            in_array($normalized, ['nl', 'nederland', 'netherlands', 'pays-bas', 'holland'], true) => 'NL',
            in_array($normalized, ['de', 'duitsland', 'germany', 'deutschland', 'allemagne'], true) => 'DE',
            in_array($normalized, ['fr', 'frankrijk', 'france'], true) => 'FR',
            mb_strlen($country) === 2 => mb_strtoupper($country),
            default => 'BE',
        };
    }

    private function cityFromLocation(string $location): ?string
    {
        $part = trim(explode(',', $location)[0] ?? '');

        return $part !== '' ? $part : null;
    }

    private function countryFromLocation(string $location): string
    {
        $parts = array_map('trim', explode(',', $location));

        if (count($parts) < 2) {
            return 'BE';
        }

        return $this->normalizeCountry((string) end($parts));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'club';
        $slug = $base;
        $suffix = 2;

        while (DB::table('clubs')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
};
