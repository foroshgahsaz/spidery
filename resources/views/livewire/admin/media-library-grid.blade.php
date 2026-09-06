<div class="media-library-grid">
    <div class="media-library-grid__toolbar">
        <div class="media-library-grid__toolbar-start">
            <strong>مرکز فایل</strong>
            <span class="media-library-grid__count">{{ $files->count() }} فایل در «{{ $directoryLabel }}»</span>
        </div>

        <div class="media-library-grid__toolbar-actions">
            @if ($files->isNotEmpty())
                <button type="button" class="media-library-grid__tool-btn" wire:click="selectAll">
                    انتخاب همه
                </button>
                <button type="button" class="media-library-grid__tool-btn" wire:click="clearSelection" @disabled(count($checkedPaths) === 0)>
                    لغو انتخاب
                </button>
                <button
                    type="button"
                    class="media-library-grid__tool-btn media-library-grid__tool-btn--danger"
                    wire:click="deleteSelected"
                    wire:confirm="فایل‌های انتخاب‌شده حذف شوند؟ این عمل قابل بازگشت نیست."
                    @disabled(count($checkedPaths) === 0)
                >
                    حذف انتخاب‌شده‌ها ({{ count($checkedPaths) }})
                </button>
            @endif
        </div>
    </div>

    @if ($files->isEmpty())
        <div class="media-library-grid__empty">
            <x-filament::icon icon="heroicon-o-photo" class="h-10 w-10" />
            <p>هنوز فایلی در این پوشه نیست.</p>
            <p class="media-library-grid__empty-sub">به تب «بارگذاری» بروید و فایل جدید آپلود کنید.</p>
        </div>
    @else
        <div class="media-library-grid__items" wire:loading.class="is-loading">
            @foreach ($files as $file)
                <div
                    class="media-library-grid__item"
                    wire:key="media-file-{{ md5($file->path) }}"
                    @class(['is-checked' => in_array($file->path, $checkedPaths, true)])
                >
                    <label class="media-library-grid__check-label">
                        <input
                            type="checkbox"
                            class="media-library-grid__checkbox"
                            @checked(in_array($file->path, $checkedPaths, true))
                            wire:click.stop="togglePath(@js($file->path))"
                        >
                    </label>

                    <button
                        type="button"
                        class="media-library-grid__pick"
                        wire:click="pickFile(@js($file->path))"
                    >
                        <span class="media-library-grid__thumb">
                            @if ($file->url)
                                <img src="{{ $file->url }}" alt="{{ $file->alt_text ?: $file->name }}" loading="lazy">
                            @endif
                        </span>
                        <span class="media-library-grid__name" title="{{ $file->name }}">{{ $file->name }}</span>
                        @if ($file->title || $file->alt_text)
                            <span class="media-library-grid__seo-badge">SEO</span>
                        @endif
                    </button>

                    <button
                        type="button"
                        class="media-library-grid__delete"
                        title="حذف"
                        wire:click.stop="deleteOne(@js($file->path))"
                        wire:confirm="این فایل حذف شود؟"
                    >
                        <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
