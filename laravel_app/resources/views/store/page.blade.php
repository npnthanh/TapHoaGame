@extends('layouts.app')
@section('title', $page->title)
@section('content')<article class="panel article"><div class="section-kicker">TẠP HÓA GAME / THÔNG TIN</div><h1>{{ $page->title }}</h1><div class="prose">{{ $page->body }}</div><p><a class="text-link" href="{{ route('support') }}">Bạn cần hỗ trợ thêm? Gửi yêu cầu</a></p></article>@endsection
