@extends('layouts.shop')

@section('title', 'مجله چاپینو | ' . config('app.name'))

@section('content')
<div class="max-w-site mx-auto px-4 md:px-6 py-6 md:py-10">
    <h1 class="section-title mb-8">مجله چاپینو</h1>

    @if($posts->isEmpty())
        <p class="text-gray-500">مقاله‌ای یافت نشد.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <a href="{{ route('blog.show', $post) }}">
                        <img src="{{ $post->image ? \App\Support\ShopMedia::url($post->image) : asset('shop/images/blog/article-1.svg') }}"
                             alt="{{ $post->title }}"
                             class="w-full h-44 object-cover"
                             loading="lazy">
                    </a>
                    <div class="p-4">
                        <a href="{{ route('blog.show', $post) }}" class="text-sm font-bold leading-7 mb-2 block hover:text-brand-gold">
                            {{ $post->title }}
                        </a>
                        @if($post->excerpt)
                            <p class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $post->excerpt }}</p>
                        @endif
                        <div class="flex items-center justify-between text-[11px] text-gray-400">
                            <span>{{ optional($post->published_at)->format('Y/m/d') }}</span>
                            @if($post->author?->slug)
                                <a href="{{ route('authors.show', $post->author) }}" class="hover:text-brand-gold">{{ $post->author->name }}</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
