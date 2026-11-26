@php($previewImage = $previewBanner->image_url ?: $previewBanner->product?->image_url)
<button class="slide-side slide-preview-{{ $direction }}" type="button" data-carousel-preview="{{ $direction }}" aria-label="{{ $direction === 'previous' ? 'Xem banner trước' : 'Xem banner tiếp theo' }}: {{ $previewBanner->title }}">
    <img @if($previewImage) src="{{ $previewImage }}" @else hidden @endif alt="" data-preview-image>
    <span class="slide-preview-title" data-preview-title>{{ $previewBanner->title }}</span>
</button>
