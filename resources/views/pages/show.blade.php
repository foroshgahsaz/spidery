@extends('layouts.shop')

@section('title', ($page->meta_title ?: $page->title) . ' | ' . site_name())

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for($page, ['type' => 'article'])" />
@endsection

@section('content')
<div class="container mx-auto px-4 py-10 max-w-3xl">
    <article class="shop-card p-6 md:p-8">
        <h1 class="text-2xl md:text-3xl font-black text-navy mb-6">{{ $page->title }}</h1>
        <div class="prose prose-sm max-w-none text-gray-700 leading-8 page-content rich-content">
            {!! $page->content !!}
        </div>
    </article>
</div>
@endsection
