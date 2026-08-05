@props(['regions' => []])

@php
    $us = collect($regions)->firstWhere('code', 'us');
    $eu = collect($regions)->firstWhere('code', 'eu-de');
    $usLabel = $us['label'] ?? 'United States';
    $euLabel = $eu['label'] ?? 'Europe (Germany)';
@endphp

<figure class="region-map">
    <svg class="region-map__svg" viewBox="0 0 640 360" role="img"
        aria-label="Hosting regions in the United States and Germany">
        <defs>
            <linearGradient id="regionMapSea" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#d8cfc0" stop-opacity="0.55" />
                <stop offset="55%" stop-color="#ebe4d8" stop-opacity="0.9" />
                <stop offset="100%" stop-color="#cfd8d2" stop-opacity="0.45" />
            </linearGradient>
            <linearGradient id="regionMapLand" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#3a342e" />
                <stop offset="100%" stop-color="#1c1916" />
            </linearGradient>
            <radialGradient id="regionMapGlow" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#c45c26" stop-opacity="0.55" />
                <stop offset="100%" stop-color="#c45c26" stop-opacity="0" />
            </radialGradient>
        </defs>

        <rect width="640" height="360" rx="18" fill="url(#regionMapSea)" />

        <path
            d="M70 95 C95 70 145 62 185 78 C220 92 245 120 238 155 C232 188 205 210 168 218 C128 227 88 205 72 168 C58 135 52 112 70 95 Z"
            fill="url(#regionMapLand)" opacity="0.92" />
        <path
            d="M145 210 C165 205 188 218 195 245 C200 268 182 292 158 298 C132 304 108 288 102 262 C96 236 120 216 145 210 Z"
            fill="url(#regionMapLand)" opacity="0.88" />

        <path
            d="M430 70 C470 55 520 62 548 90 C572 114 578 150 562 180 C548 206 512 220 478 214 C448 208 422 186 416 154 C410 122 412 88 430 70 Z"
            fill="url(#regionMapLand)" opacity="0.92" />
        <path
            d="M455 175 C478 168 505 178 518 200 C530 220 522 248 498 258 C474 268 446 252 438 228 C430 204 438 182 455 175 Z"
            fill="url(#regionMapLand)" opacity="0.86" />

        <path d="M250 175 C310 155 360 165 410 175" fill="none" stroke="#b45309" stroke-width="1.5"
            stroke-dasharray="5 7" opacity="0.45" />

        <g class="region-map__marker">
            <circle cx="155" cy="145" r="22" fill="url(#regionMapGlow)" />
            <circle cx="155" cy="145" r="7" fill="#c45c26" />
            <circle cx="155" cy="145" r="3.2" fill="#ebe4d8" />
            <rect x="175" y="126" width="128" height="34" rx="8" fill="#ebe4d8" stroke="#3a342e"
                stroke-opacity="0.18" />
            <text x="239" y="148" text-anchor="middle" fill="#1c1916" font-size="13" font-weight="650"
                font-family="Sora, ui-sans-serif, system-ui, sans-serif">{{ $usLabel }}</text>
        </g>

        <g class="region-map__marker">
            <circle cx="505" cy="145" r="22" fill="url(#regionMapGlow)" />
            <circle cx="505" cy="145" r="7" fill="#b45309" />
            <circle cx="505" cy="145" r="3.2" fill="#ebe4d8" />
            <rect x="348" y="126" width="140" height="34" rx="8" fill="#ebe4d8" stroke="#3a342e"
                stroke-opacity="0.18" />
            <text x="418" y="148" text-anchor="middle" fill="#1c1916" font-size="13" font-weight="650"
                font-family="Sora, ui-sans-serif, system-ui, sans-serif">{{ $euLabel }}</text>
        </g>
    </svg>
</figure>
