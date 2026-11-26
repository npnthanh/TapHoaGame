@props(['href'])
<a href="{{ $href }}" {{ $attributes->class(['section-link']) }}>
    <span>{{ $slot }}</span>
    <span class="section-link-icon"><x-icon name="chevron-right"/></span>
</a>
