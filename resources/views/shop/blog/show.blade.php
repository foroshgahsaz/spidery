@extends('layouts.shop')

@section('title', $post->title . ' | ' . site_name())

@section('content')
<article class="max-w-site mx-auto px-4 md:px-6 py-6 md:py-10">
    <nav class="text-sm text-gray-400 mb-6">
        <a href="{{ route('home') }}" class="hover:text-brand-gold">خانه</a>
        <span class="mx-2">/</span>
        <a href="{{ route('blog.index') }}" class="hover:text-brand-gold">مجله</a>
        <span class="mx-2">/</span>
        <span class="text-gray-600">{{ $post->title }}</span>
    </nav>

    <header class="mb-8">
        <h1 class="text-2xl md:text-3xl font-bold mb-4">{{ $post->title }}</h1>
        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
            @if($post->published_at)
                <span>{{ $post->published_at->format('Y/m/d') }}</span>
            @endif
            @if($post->author?->slug)
                <a href="{{ route('authors.show', $post->author) }}" class="hover:text-brand-gold">
                    {{ $post->author->name }}
                </a>
            @endif
        </div>
    </header>

    @if($post->image)
        <img src="{{ \App\Support\ShopMedia::url($post->image) }}" alt="{{ $post->title }}" class="w-full max-h-[420px] object-cover rounded-2xl mb-8">
    @endif

    <x-shop.rich-content :content="$post->content" />

    @if($relatedPosts->isNotEmpty())
        <section class="mt-12 pt-8 border-t border-gray-100">
            <h2 class="section-title mb-6">مقالات مرتبط</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($relatedPosts as $related)
                    <a href="{{ route('blog.show', $related) }}" class="block p-4 rounded-xl border border-gray-100 hover:border-brand-gold/30">
                        <h3 class="font-bold text-sm">{{ $related->title }}</h3>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</article>
@endsection
