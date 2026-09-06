@php
    use App\Support\ShopMedia;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    $id = $getId();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $accepted = $getAcceptedFileTypes() ?: ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
    $maxSizeKb = $getMaxSize() ?: 51200;

    $previewUrl = null;
    $state = $getState();

    if (is_array($state)) {
        foreach ($state as $item) {
            if ($item instanceof TemporaryUploadedFile) {
                $previewUrl = rescue(fn () => $item->temporaryUrl(), report: false);
                break;
            }

            if (is_string($item) && $item !== '') {
                $previewUrl = ShopMedia::url($item);
                break;
            }
        }
    }
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    label-tag="div"
>
    <div
        wire:ignore
        wire:key="{{ $this->getId() }}.{{ $statePath }}.shop-image-upload"
        class="shop-image-upload"
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            uploading: false,
            progress: 0,
            error: null,
            existingPreview: @js($previewUrl),
            localPreview: null,
            maxSizeKb: {{ (int) $maxSizeKb }},
            get preview() {
                return this.localPreview || this.existingPreview
            },
            uuid() {
                if (crypto.randomUUID) {
                    return crypto.randomUUID()
                }

                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                    const r = Math.random() * 16 | 0
                    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16)
                })
            },
            onChange(event) {
                const file = event.target.files?.[0]
                event.target.value = ''

                if (! file) {
                    return
                }

                if (file.size > this.maxSizeKb * 1024) {
                    this.error = 'حجم فایل بیش از حد مجاز است.'
                    if (window.shopAdminAlert) {
                        window.shopAdminAlert({
                            title: 'حجم فایل زیاد است',
                            body: 'فایل انتخابی بیش از حد مجاز است.',
                            status: 'warning',
                        })
                    }
                    return
                }

                this.error = null
                this.uploading = true
                this.progress = 0
                this.localPreview = URL.createObjectURL(file)

                const key = this.uuid()
                this.state = {}

                $wire.upload(
                    @js($statePath) + '.' + key,
                    file,
                    () => {
                        this.uploading = false
                        this.progress = 100
                        if (window.shopAdminToast) {
                            window.shopAdminToast('آپلود تصویر', 'success', 'فایل با موفقیت آپلود شد. برای ثبت نهایی دکمه ذخیره را بزنید.')
                        }
                    },
                    () => {
                        this.uploading = false
                        this.progress = 0
                        this.localPreview = null
                        this.error = 'فایل آپلود نشد. دوباره انتخاب کنید و تا پایان نوار پیشرفت صبر کنید.'
                        if (window.shopAdminAlert) {
                            window.shopAdminAlert({
                                title: 'خطا در آپلود',
                                body: this.error,
                                status: 'danger',
                            })
                        }
                    },
                    (progressEvent) => {
                        this.progress = progressEvent.detail.progress ?? 0
                    },
                )
            },
            clear() {
                this.state = {}
                this.localPreview = null
                this.existingPreview = null
                this.progress = 0
                this.uploading = false
                this.error = null
                if (window.shopAdminToast) {
                    window.shopAdminToast('حذف تصویر', 'info', 'تصویر از فرم حذف شد. برای ثبت نهایی دکمه ذخیره را بزنید.')
                }
            },
        }"
    >
        <div class="shop-image-upload__box">
            <template x-if="preview">
                <img :src="preview" alt="" class="shop-image-upload__preview" x-on:error="if (! localPreview) { existingPreview = null }">
            </template>

            <template x-if="! preview">
                <div class="shop-image-upload__empty">
                    <span>تصویری انتخاب نشده</span>
                </div>
            </template>

            <div class="shop-image-upload__actions">
                <label class="shop-image-upload__choose">
                    <span x-text="preview ? 'تغییر فایل' : 'انتخاب فایل'">انتخاب فایل</span>
                    <input
                        x-ref="input"
                        type="file"
                        accept="{{ implode(',', $accepted) }}"
                        @disabled($isDisabled)
                        class="shop-image-upload__input"
                        aria-labelledby="{{ $id }}-label"
                        x-on:change="onChange($event)"
                    />
                </label>

                <button
                    type="button"
                    class="shop-image-upload__clear"
                    x-show="preview && ! uploading"
                    x-cloak
                    x-on:click.prevent="clear()"
                    @disabled($isDisabled)
                >
                    حذف
                </button>
            </div>
        </div>

        <div class="shop-image-upload__progress" x-show="uploading || progress > 0 && progress < 100" x-cloak>
            <div class="shop-image-upload__progress-bar" :style="`width: ${progress}%`"></div>
            <span class="shop-image-upload__progress-label" x-text="`در حال آپلود ${progress}٪`"></span>
        </div>

        <div
            class="shop-image-upload__error"
            x-show="error"
            x-text="error"
            x-cloak
        ></div>
    </div>
</x-dynamic-component>
