<?php

use App\Models\StoryPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_pages', function (Blueprint $table) {
            $table->id();

            foreach (StoryPage::VISIBLE_COLUMNS as $column) {
                $table->boolean($column)->default(true);
            }

            foreach ([...StoryPage::UNTRANSLATED_COLUMNS, ...StoryPage::TRANSLATION_COLUMNS] as $column) {
                $table->text($column);
            }

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_pages');
    }
};
