@props(['name' => 'grid'])
<svg {{ $attributes->class(['ui-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('bolt')
            <path d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z"/>
            @break
        @case('chevron-right')
            <path d="m9 5 7 7-7 7"/>
            @break
        @case('gamepad')
            <path d="M8 7h8a4 4 0 0 1 3.9 3.1l1.5 6.5a2.6 2.6 0 0 1-4.3 2.5L14.5 17h-5L6.9 19a2.6 2.6 0 0 1-4.3-2.5l1.5-6.4A4 4 0 0 1 8 7Z"/><path d="M8 10v5m-2.5-2.5h5M15.5 11h.01M18 13.5h.01"/>
            @break
        @case('monitor')
            <rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/>
            @break
        @case('layers')
            <path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5"/>
            @break
        @case('gift')
            <rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v8h14v-8M12 8v12"/><path d="M12 8H8a2.5 2.5 0 1 1 2.4-3.2L12 8Zm0 0h4a2.5 2.5 0 1 0-2.4-3.2L12 8Z"/>
            @break
        @default
            <rect x="3.5" y="3.5" width="6" height="6" rx="1.5"/><rect x="14.5" y="3.5" width="6" height="6" rx="1.5"/><rect x="3.5" y="14.5" width="6" height="6" rx="1.5"/><rect x="14.5" y="14.5" width="6" height="6" rx="1.5"/>
    @endswitch
</svg>
