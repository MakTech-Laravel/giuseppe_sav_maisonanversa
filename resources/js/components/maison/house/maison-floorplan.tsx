import { useTranslation } from 'react-i18next';
import { FloorplanRoom } from '@/components/maison/house/floorplan-room';
import { useLocale } from '@/hooks/use-locale';
import { foundingProductUrl } from '@/lib/maison-navigation';

/**
 * The eight-room elevation drawing. Geometry is taken verbatim from the
 * prototype; only the click handlers became real links.
 */
export function MaisonFloorplan() {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <svg
            className="maison-svg mx-auto block h-auto w-full max-w-250"
            viewBox="0 0 1100 1280"
            role="img"
            aria-label={t(
                'Acht kamers, acht hoofdstukken. Stap binnen door op een ruimte te klikken — elke kamer brengt u naar een deel van het huis.',
            )}
        >
            {/* Roof */}
            <path
                className="structure"
                d="M40,300 L150,150 L950,150 L1060,300"
            />
            <line className="structure" x1="40" y1="300" x2="1060" y2="300" />
            <rect className="structure" x="296" y="80" width="40" height="74" />
            <rect className="structure" x="704" y="68" width="40" height="86" />
            <line className="structure" x1="290" y1="80" x2="342" y2="80" />
            <line className="structure" x1="698" y1="68" x2="750" y2="68" />
            <path
                className="structure"
                d="M470,232 L470,178 L630,178 L630,232 Z"
            />
            <path className="structure" d="M458,178 L550,142 L642,178" />
            <line className="structure" x1="550" y1="142" x2="550" y2="232" />
            <line className="structure" x1="470" y1="205" x2="630" y2="205" />

            {/* Attic: Club Corner */}
            <FloorplanRoom to="corner">
                <polygon
                    className="room-fill"
                    points="70,470 70,300 150,160 950,160 1030,300 1030,470"
                />
                <polygon
                    className="room-tint"
                    points="70,470 70,300 150,160 950,160 1030,300 1030,470"
                    fill="rgba(150,110,70,0.09)"
                />
                <g className="furn">
                    <ellipse cx="420" cy="360" rx="52" ry="40" />
                    <line x1="420" y1="400" x2="404" y2="452" />
                    <line x1="430" y1="398" x2="448" y2="450" />
                    <rect x="640" y="402" width="220" height="26" rx="4" />
                    <line x1="660" y1="428" x2="660" y2="452" />
                    <line x1="840" y1="428" x2="840" y2="452" />
                </g>
                <text
                    className="room-label-name"
                    x="550"
                    y="330"
                    textAnchor="middle"
                >
                    Club Corner
                </text>
                <text
                    className="room-label-sub"
                    x="550"
                    y="352"
                    textAnchor="middle"
                >
                    {t('De speelkamer')}
                </text>
            </FloorplanRoom>

            <line className="wall" x1="70" y1="470" x2="1030" y2="470" />
            <line className="wall" x1="70" y1="710" x2="1030" y2="710" />
            <line className="wall" x1="70" y1="950" x2="1030" y2="950" />

            {/* 2nd floor */}
            <line className="wall" x1="550" y1="470" x2="550" y2="710" />
            <FloorplanRoom to="circle">
                <rect
                    className="room-fill"
                    x="70"
                    y="470"
                    width="480"
                    height="240"
                />
                <rect
                    className="room-tint"
                    x="70"
                    y="470"
                    width="480"
                    height="240"
                    fill="rgba(120,50,70,0.11)"
                />
                <rect
                    className="rug"
                    x="150"
                    y="560"
                    width="200"
                    height="90"
                    rx="6"
                />
                <g className="furn">
                    <circle cx="250" cy="600" r="34" />
                    <circle cx="160" cy="560" r="12" />
                    <circle cx="160" cy="640" r="12" />
                    <circle cx="340" cy="560" r="12" />
                    <circle cx="340" cy="640" r="12" />
                </g>
                <text className="room-label-name" x="430" y="510">
                    Le Cercle
                </text>
                <text className="room-label-sub" x="430" y="530">
                    Founding Circle
                </text>
            </FloorplanRoom>
            <FloorplanRoom to="journal">
                <rect
                    className="room-fill"
                    x="550"
                    y="470"
                    width="480"
                    height="240"
                />
                <rect
                    className="room-tint"
                    x="550"
                    y="470"
                    width="480"
                    height="240"
                    fill="rgba(60,85,75,0.12)"
                />
                <g className="furn">
                    <rect x="600" y="490" width="14" height="170" />
                    <rect x="618" y="490" width="14" height="170" />
                    <rect x="636" y="490" width="14" height="170" />
                    <line x1="600" y1="520" x2="650" y2="520" />
                    <line x1="600" y1="560" x2="650" y2="560" />
                    <line x1="600" y1="600" x2="650" y2="600" />
                    <line x1="600" y1="640" x2="650" y2="640" />
                </g>
                <text
                    className="room-label-name"
                    x="760"
                    y="610"
                    textAnchor="middle"
                >
                    {t('Bibliotheek')}
                </text>
                <text
                    className="room-label-sub"
                    x="760"
                    y="632"
                    textAnchor="middle"
                >
                    Journal
                </text>
            </FloorplanRoom>

            {/* 1st floor */}
            <line className="wall" x1="550" y1="710" x2="550" y2="950" />
            <FloorplanRoom to="story">
                <rect
                    className="room-fill"
                    x="70"
                    y="710"
                    width="480"
                    height="240"
                />
                <rect
                    className="room-tint"
                    x="70"
                    y="710"
                    width="480"
                    height="240"
                    fill="rgba(150,90,60,0.11)"
                />
                <rect
                    className="rug"
                    x="140"
                    y="800"
                    width="160"
                    height="120"
                    rx="6"
                />
                <g className="furn">
                    <line x1="180" y1="940" x2="150" y2="740" />
                    <line x1="220" y1="940" x2="250" y2="740" />
                    <line x1="150" y1="740" x2="250" y2="740" />
                    <rect x="165" y="760" width="70" height="90" />
                    <rect x="360" y="870" width="150" height="20" />
                    <line x1="372" y1="890" x2="372" y2="940" />
                    <line x1="498" y1="890" x2="498" y2="940" />
                </g>
                <text className="room-label-name" x="430" y="760">
                    {t('Atelier')}
                </text>
                <text className="room-label-sub" x="430" y="780">
                    {t('Ons Verhaal')}
                </text>
            </FloorplanRoom>
            <FloorplanRoom to="community">
                <rect
                    className="room-fill"
                    x="550"
                    y="710"
                    width="480"
                    height="240"
                />
                <rect
                    className="room-tint"
                    x="550"
                    y="710"
                    width="480"
                    height="240"
                    fill="rgba(60,75,100,0.11)"
                />
                <rect
                    className="rug"
                    x="610"
                    y="760"
                    width="360"
                    height="130"
                    rx="6"
                />
                <g className="furn">
                    <rect x="620" y="800" width="340" height="50" rx="8" />
                    <circle cx="660" cy="770" r="9" />
                    <circle cx="760" cy="770" r="9" />
                    <circle cx="860" cy="770" r="9" />
                    <circle cx="660" cy="880" r="9" />
                    <circle cx="760" cy="880" r="9" />
                    <circle cx="860" cy="880" r="9" />
                </g>
                <text
                    className="room-label-name"
                    x="790"
                    y="752"
                    textAnchor="middle"
                >
                    Salle Commune
                </text>
                <text
                    className="room-label-sub"
                    x="790"
                    y="776"
                    textAnchor="middle"
                >
                    Community
                </text>
            </FloorplanRoom>

            {/* Ground floor */}
            <line className="wall" x1="400" y1="950" x2="400" y2="1180" />
            <line className="wall" x1="760" y1="950" x2="760" y2="1180" />
            <FloorplanRoom to="home">
                <rect
                    className="room-fill"
                    x="70"
                    y="950"
                    width="330"
                    height="230"
                />
                <rect
                    className="room-tint"
                    x="70"
                    y="950"
                    width="330"
                    height="230"
                    fill="rgba(175,140,80,0.11)"
                />
                <rect
                    className="rug"
                    x="100"
                    y="1085"
                    width="180"
                    height="80"
                    rx="6"
                />
                <g className="furn">
                    <line x1="235" y1="950" x2="235" y2="1000" />
                    <circle cx="235" cy="1006" r="10" />
                    <line x1="220" y1="1010" x2="250" y2="1010" />
                    <rect x="110" y="1110" width="150" height="40" rx="6" />
                    <rect x="110" y="1080" width="150" height="34" rx="6" />
                </g>
                <text
                    className="room-label-name"
                    x="235"
                    y="1060"
                    textAnchor="middle"
                >
                    Salon
                </text>
                <text
                    className="room-label-sub"
                    x="235"
                    y="1080"
                    textAnchor="middle"
                >
                    {t('Onthaal · Home')}
                </text>
            </FloorplanRoom>
            <FloorplanRoom href={foundingProductUrl(locale)}>
                <rect
                    className="room-fill"
                    x="400"
                    y="950"
                    width="360"
                    height="230"
                />
                <rect
                    className="room-tint"
                    x="400"
                    y="950"
                    width="360"
                    height="230"
                    fill="rgba(90,90,95,0.10)"
                />
                <rect
                    className="rug"
                    x="520"
                    y="1075"
                    width="120"
                    height="90"
                    rx="6"
                />
                <g className="furn">
                    <rect x="560" y="1080" width="40" height="60" />
                    <rect x="548" y="1076" width="64" height="8" />
                    <ellipse cx="580" cy="1040" rx="30" ry="24" />
                    <line x1="580" y1="1064" x2="572" y2="1080" />
                    <line x1="588" y1="1064" x2="596" y2="1080" />
                </g>
                <text
                    className="room-label-name"
                    x="580"
                    y="990"
                    textAnchor="middle"
                >
                    {t('Galerie')}
                </text>
                <text
                    className="room-label-sub"
                    x="580"
                    y="1010"
                    textAnchor="middle"
                >
                    Heritage No.001
                </text>
            </FloorplanRoom>
            <FloorplanRoom to="contact">
                <rect
                    className="room-fill"
                    x="760"
                    y="950"
                    width="270"
                    height="230"
                />
                <rect
                    className="room-tint"
                    x="760"
                    y="950"
                    width="270"
                    height="230"
                    fill="rgba(110,110,70,0.11)"
                />
                <rect
                    className="rug"
                    x="790"
                    y="1095"
                    width="170"
                    height="70"
                    rx="6"
                />
                <g className="furn">
                    <rect x="800" y="1100" width="150" height="22" />
                    <line x1="816" y1="1122" x2="816" y2="1170" />
                    <line x1="934" y1="1122" x2="934" y2="1170" />
                    <line x1="895" y1="1100" x2="895" y2="1060" />
                    <circle cx="895" cy="1052" r="12" />
                    <line x1="884" y1="1056" x2="906" y2="1056" />
                </g>
                <text
                    className="room-label-name"
                    x="895"
                    y="990"
                    textAnchor="middle"
                >
                    {t('Bureau')}
                </text>
                <text
                    className="room-label-sub"
                    x="895"
                    y="1010"
                    textAnchor="middle"
                >
                    {t('Contact')}
                </text>
            </FloorplanRoom>

            <g className="detail" aria-hidden="true">
                <rect
                    className="window"
                    x="86"
                    y="525"
                    width="12"
                    height="120"
                />
                <line className="window" x1="92" y1="525" x2="92" y2="645" />
                <rect
                    className="window"
                    x="1002"
                    y="525"
                    width="12"
                    height="120"
                />
                <line
                    className="window"
                    x1="1008"
                    y1="525"
                    x2="1008"
                    y2="645"
                />
                <rect
                    className="window"
                    x="86"
                    y="765"
                    width="12"
                    height="120"
                />
                <line className="window" x1="92" y1="765" x2="92" y2="885" />
                <rect
                    className="window"
                    x="1002"
                    y="765"
                    width="12"
                    height="120"
                />
                <line
                    className="window"
                    x1="1008"
                    y1="765"
                    x2="1008"
                    y2="885"
                />
                <rect
                    className="window"
                    x="86"
                    y="985"
                    width="12"
                    height="110"
                />
                <line className="window" x1="92" y1="985" x2="92" y2="1095" />
                <rect
                    className="window"
                    x="1002"
                    y="985"
                    width="12"
                    height="110"
                />
                <line
                    className="window"
                    x1="1008"
                    y1="985"
                    x2="1008"
                    y2="1095"
                />
                <line className="lamp" x1="550" y1="300" x2="550" y2="345" />
                <circle className="lamp-bulb" cx="550" cy="352" r="6" />
                <line className="lamp" x1="250" y1="470" x2="250" y2="510" />
                <circle className="lamp-bulb" cx="250" cy="517" r="6" />
                <line className="lamp" x1="760" y1="470" x2="760" y2="510" />
                <circle className="lamp-bulb" cx="760" cy="517" r="6" />
                <line className="lamp" x1="200" y1="710" x2="200" y2="750" />
                <circle className="lamp-bulb" cx="200" cy="757" r="6" />
                <line className="lamp" x1="790" y1="710" x2="790" y2="750" />
                <circle className="lamp-bulb" cx="790" cy="757" r="6" />
                <line className="lamp" x1="235" y1="950" x2="235" y2="985" />
                <circle className="lamp-bulb" cx="235" cy="991" r="6" />
                <line className="lamp" x1="580" y1="950" x2="580" y2="985" />
                <circle className="lamp-bulb" cx="580" cy="991" r="6" />
                <line className="lamp" x1="895" y1="950" x2="895" y2="985" />
                <circle className="lamp-bulb" cx="895" cy="991" r="6" />
            </g>

            <rect className="wall" x="70" y="300" width="960" height="880" />
            <path
                className="wall"
                d="M556,1180 L556,1110 L604,1110 L604,1180"
            />
            <line className="wall" x1="580" y1="1110" x2="580" y2="1180" />
            <line className="ground" x1="520" y1="1192" x2="640" y2="1192" />
            <line className="ground" x1="500" y1="1204" x2="660" y2="1204" />
            <line className="ground" x1="0" y1="1218" x2="1100" y2="1218" />
        </svg>
    );
}
