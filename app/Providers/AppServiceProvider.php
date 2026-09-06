<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use App\Http\Controllers\LivewireFileUploadController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\HomeSliderObserver;
use App\Observers\MediaUsageObserver;
use App\Observers\MenuItemObserver;
use App\Observers\PostObserver;
use App\Observers\ProductImageObserver;
use App\Observers\ProductObserver;
use App\Observers\ShippingMethodObserver;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserAddressPolicy;
use App\Services\Cache\ShopCacheService;
use App\Services\Media\ImageOptimizer;
use App\Services\Media\MediaRegistry;
use App\Services\Settings\SettingsService;
use App\Services\Sms\KavenegarSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Support\StoragePermissionFixer;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FileUploadController::class, LivewireFileUploadController::class);

        $this->app->bind(SmsSender::class, function () {
            $kavenegar = app(SettingsService::class)->kavenegar();

            if (($kavenegar['enabled'] ?? false) && ! empty($kavenegar['api_key'])) {
                return new KavenegarSmsSender;
            }

            return match (config('sms.driver')) {
                'kavenegar' => new KavenegarSmsSender,
                default => new LogSmsSender,
            };
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole() && StoragePermissionFixer::runningAsRoot()) {
            StoragePermissionFixer::fix();
        }

        $forceHttps = $this->shouldForceHttps();

        if ($forceHttps) {
            URL::forceScheme('https');
            config(['session.secure' => true]);
        }

        if (! $this->app->runningInConsole() && request()->hasHeader('Host')) {
            $scheme = $forceHttps || request()->isSecure()
                ? 'https'
                : request()->getScheme();

            URL::forceRootUrl($scheme.'://'.request()->getHttpHost());
        }

        $productsPath = Storage::disk('public')->path('products');

        if (! is_dir($productsPath)) {
            Storage::disk('public')->makeDirectory('products');
        }

        $this->ensureWritableDirectory($productsPath);

        $tempDisk = config('livewire.temporary_file_upload.disk', 'livewire-tmp');
        $tempDirectory = config('livewire.temporary_file_upload.directory', 'livewire-tmp');
        $tempDirectoryPath = Storage::disk($tempDisk)->path($tempDirectory);

        if (! is_dir($tempDirectoryPath)) {
            Storage::disk($tempDisk)->makeDirectory($tempDirectory);
        }

        $this->ensureWritableDirectory($tempDirectoryPath);

        Product::observe(ProductObserver::class);
        ProductImage::observe(ProductImageObserver::class);
        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        Post::observe(PostObserver::class);
        HomeSlider::observe(HomeSliderObserver::class);
        ShippingMethod::observe(ShippingMethodObserver::class);

        $mediaObserver = app(MediaUsageObserver::class);

        foreach (array_keys(config('media-library.models', [])) as $modelClass) {
            if (is_string($modelClass) && class_exists($modelClass)) {
                $modelClass::observe($mediaObserver);
            }
        }

        View::composer([
            'shop.partials.header',
            'shop.partials.overlays',
            'shop.partials.mobile-menu',
        ], function ($view) {
            $view->with(app(ShopCacheService::class)->headerPayload());
        });

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(UserAddress::class, UserAddressPolicy::class);

        Route::bind('author', function (string $value) {
            return User::query()
                ->where('slug', $value)
                ->where('is_author', true)
                ->firstOrFail();
        });

        \Filament\Tables\Actions\CreateAction::configureUsing(function ($action): void {
            $action
                ->label('افزودن')
                ->successNotification(CrudSuccessNotification::created())
                ->beforeFormValidated(function ($action): void {
                    self::sanitizeMountedActionUploads($action);
                });
        });
        \Filament\Tables\Actions\EditAction::configureUsing(function ($action): void {
            $action
                ->label('ویرایش')
                ->successNotification(CrudSuccessNotification::saved())
                ->beforeFormValidated(function ($action): void {
                    self::sanitizeMountedActionUploads($action, $action->getRecord());
                });
        });
        \Filament\Tables\Actions\DeleteAction::configureUsing(function ($action): void {
            $action
                ->label('حذف')
                ->successNotification(CrudSuccessNotification::deleted());
        });
        ViewAction::configureUsing(fn ($action) => $action->label('مشاهده'));
        DeleteBulkAction::configureUsing(function ($action): void {
            $action
                ->label('حذف انتخاب‌شده‌ها')
                ->successNotification(CrudSuccessNotification::deleted());
        });
        CreateAction::configureUsing(function ($action): void {
            $action
                ->label('افزودن')
                ->successNotification(CrudSuccessNotification::created())
                ->beforeFormValidated(function ($action): void {
                    self::sanitizeMountedActionUploads($action);
                });
        });
        EditAction::configureUsing(function ($action): void {
            $action
                ->label('ویرایش')
                ->successNotification(CrudSuccessNotification::saved())
                ->beforeFormValidated(function ($action): void {
                    self::sanitizeMountedActionUploads($action, $action->getRecord());
                });
        });
        DeleteAction::configureUsing(function ($action): void {
            $action
                ->label('حذف')
                ->successNotification(CrudSuccessNotification::deleted());
        });

        FileUpload::configureUsing(function (FileUpload $component): void {
            $component
                ->fetchFileInformation(true)
                ->maxSize(51200)
                ->imagePreviewHeight('150')
                ->helperText('تا پایان آپلود (نوار پیشرفت) صبر کنید، بعد ذخیره کنید. حداکثر ۵۰ مگابایت.')
                ->validationMessages([
                    'uploaded' => 'فایل آپلود نشد. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
                ])
                ->getUploadedFileUsing(function (FileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                    $storage = $component->getDisk();

                    if (! $storage->exists($file)) {
                        return null;
                    }

                    $url = $component->getVisibility() === 'private'
                        ? rescue(fn () => $storage->temporaryUrl($file, now()->addMinutes(5)), report: false)
                        : null;

                    $url ??= $storage->url($file);

                    return [
                        'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                        'size' => $storage->size($file),
                        'type' => $storage->mimeType($file),
                        'url' => $url,
                    ];
                })
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) use ($component): ?string {
                    $disk = $component->getDiskName();
                    $directory = trim((string) $component->getDirectory(), '/');
                    $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
                    $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'bin';
                    $filename = Str::ulid().'.'.$extension;
                    $relativeDirectory = $directory !== '' ? $directory : 'uploads';

                    if (! $file->isValid()) {
                        throw ValidationException::withMessages([
                            $component->getName() => 'فایل موقت آپلود منقضی شده. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
                        ]);
                    }

                    $path = $file->storeAs($relativeDirectory, $filename, ['disk' => $disk]);

                    if (! Storage::disk($disk)->exists($path)) {
                        throw ValidationException::withMessages([
                            $component->getName() => 'ذخیره فایل روی سرور انجام نشد. لطفاً دوباره آپلود کنید.',
                        ]);
                    }

                    $path = app(ImageOptimizer::class)->optimize($disk, $path, $relativeDirectory);

                    app(MediaRegistry::class)->registerFromPath(
                        $disk,
                        $path,
                        $file->getClientOriginalName(),
                    );

                    return $path;
                })
                ->deleteUploadedFileUsing(function (): void {
                    // Physical files are kept until shop:prune-unused-media removes orphans.
                });
        }, isImportant: true);

        FileUpload::configureUsing(function (FileUpload $component): void {
            $component->afterStateHydrated(function (FileUpload $component, $state): void {
                if (filled($state)) {
                    return;
                }

                $record = $component->getRecord();

                if ($record === null) {
                    return;
                }

                $path = $record->getAttribute($component->getName());

                if (! is_string($path) || $path === '') {
                    return;
                }

                $component->state([(string) Str::uuid() => $path]);
            });
        }, isImportant: true);
    }

    protected static function sanitizeMountedActionUploads(object $action, ?Model $record = null): void
    {
        $livewire = $action->getLivewire();

        if (! method_exists($livewire, 'getMountedTableActionForm')) {
            return;
        }

        $form = $livewire->getMountedTableActionForm(mountedAction: $action);

        if ($form === null) {
            return;
        }

        FileUploadSanitizer::sanitize($livewire, $form, $record);
    }

    protected function shouldForceHttps(): bool
    {
        if ($this->app->environment('production')) {
            return true;
        }

        return str_starts_with((string) config('app.url'), 'https://');
    }

    protected function ensureWritableDirectory(string $path): void
    {
        if (! is_dir($path) || is_writable($path)) {
            return;
        }

        @chmod($path, 0775);

        if (! is_writable($path)) {
            @chmod($path, 0777);
        }
    }
}
