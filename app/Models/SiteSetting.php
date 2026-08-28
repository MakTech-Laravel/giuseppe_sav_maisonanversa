<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'announcement_text',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'whatsapp',
        'email_hello',
        'email_press',
        'instagram_url',
        'boutique_lat',
        'boutique_lng',
        'announcement_text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'boutique_lat' => 'decimal:7',
            'boutique_lng' => 'decimal:7',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing !== null) {
            return $existing;
        }

        return static::query()->create([
            'phone' => '+32400000000',
            'whatsapp' => '32400000000',
            'email_hello' => 'hello@maisonanversa.com',
            'email_press' => 'press@maisonanversa.com',
            'instagram_url' => 'https://www.instagram.com/',
            'boutique_lat' => '51.2200000',
            'boutique_lng' => '4.4040000',
        ]);
    }

    /**
     * @return array{
     *     phone: string,
     *     whatsapp: string,
     *     emailHello: string,
     *     emailPress: string,
     *     instagramUrl: string,
     *     boutiqueLat: float,
     *     boutiqueLng: float,
     *     boutiqueMapSrc: string,
     *     whatsappHref: string,
     *     phoneHref: string,
     *     emailHelloHref: string,
     *     announcementText: string|null
     * }
     */
    public function toShare(): array
    {
        $lat = (float) $this->boutique_lat;
        $lng = (float) $this->boutique_lng;
        $bboxLngMin = number_format($lng - 0.016, 3, '.', '');
        $bboxLngMax = number_format($lng + 0.016, 3, '.', '');
        $bboxLatMin = number_format($lat - 0.014, 3, '.', '');
        $bboxLatMax = number_format($lat + 0.014, 3, '.', '');
        $announcement = trim($this->translated('announcement_text'));

        return [
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'emailHello' => $this->email_hello,
            'emailPress' => $this->email_press,
            'instagramUrl' => $this->instagram_url,
            'boutiqueLat' => $lat,
            'boutiqueLng' => $lng,
            'boutiqueMapSrc' => "https://www.openstreetmap.org/export/embed.html?bbox={$bboxLngMin}%2C{$bboxLatMin}%2C{$bboxLngMax}%2C{$bboxLatMax}&layer=mapnik&marker={$lat}%2C{$lng}",
            'whatsappHref' => 'https://wa.me/'.$this->whatsapp,
            'phoneHref' => 'tel:'.$this->phone,
            'emailHelloHref' => 'mailto:'.$this->email_hello,
            'announcementText' => $announcement !== '' ? $announcement : null,
        ];
    }
}
