<?php

namespace Database\Factories;

use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionMessageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunitySessionMessageAttachment> */
class CommunitySessionMessageAttachmentFactory extends Factory
{
    protected $model = CommunitySessionMessageAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->uuid().'.jpg';

        return [
            'message_id' => CommunitySessionMessage::factory(),
            'path' => 'session-chat/1/'.$name,
            'original_name' => 'photo.jpg',
            'mime' => 'image/jpeg',
            'size' => 12_345,
        ];
    }
}
