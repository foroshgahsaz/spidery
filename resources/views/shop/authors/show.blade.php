@extends('layouts.shop')

@section('title', $author->name . ' | نویسندگان | ' . site_name())

@section('content')
<div class="max-w-site mx-auto px-4 md:px-6 py-6 md:py-10">
    <header class="mb-8 p-6 rounded-2xl bg-gray-50 border border-gray-100">
        <h1 class="text-2xl font-bold mb-2">{{ $author->name }}</h1>
        @if($author->bio)
            <p class="text-sm text-gray-600 leading-7">{{ $author->bio }}</p>
        @endif
    </header>

    <h2 class="section-title mb-6">مقالات {{ $author->name }}</h2>

    @if($posts->isEmpty())
        <p class="text-gray-500">مقاله‌ای از این نویسنده یافت نشد.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100">
                    <a href="{{ route('blog.show', $post) }}">
                        <img src="{{ $post->image ? \App\Support\ShopMedia::url($post->image) : asset('shop/images/blog/article-1.svg') }}"
                             alt="{{ $post->title }}"
                             class="w-full h-40 object-cover"
                             loading="lazy">
                    </a>
                    <div class="p-4">
                        <a href="{{ route('blog.show', $post) }}" class="text-sm font-bold leading-7 block hover:text-brand-gold">
                            {{ $post->title }}
                        </a>
                        <p class="text-[11px] text-gray-400 mt-2">{{ optional($post->published_at)->format('Y/m/d') }}</p>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
