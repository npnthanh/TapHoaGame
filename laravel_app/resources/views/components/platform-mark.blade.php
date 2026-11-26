@props(['platform', 'region'])
<span class="platform-mark" role="img" aria-label="{{ $platform }} · {{ $region }}" title="{{ $platform }} · {{ $region }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        @switch(mb_strtolower($platform))
            @case('steam')
                <circle cx="12" cy="12" r="10" fill="currentColor" fill-opacity=".1" stroke="none"/><circle cx="15.5" cy="8" r="4"/><circle cx="15.5" cy="8" r="2.2"/><circle cx="6.5" cy="17" r="3"/><path d="m8.8 15 3.8-4M9.5 17l7.4-5.2M3.8 15.8 1 14.5"/>
                @break
            @case('xbox')
                <circle cx="12" cy="12" r="9.5"/><path d="M5.5 5.1C9 5.1 15 12 19 19M18.5 5.1C15 5.1 9 12 5 19"/>
                @break
            @case('microsoft')
            @case('windows')
                <path d="M3 4h8v7H3zm10 0h8v7h-8zM3 13h8v7H3zm10 0h8v7h-8z" fill="currentColor" stroke="none"/>
                @break
            @case('rockstar')
                <rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 17V7h4a3 3 0 0 1 0 6H8m4 0 4 4"/>
                @break
            @default
                <rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/>
        @endswitch
    </svg>
</span>
