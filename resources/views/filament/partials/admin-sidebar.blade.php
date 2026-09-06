@php
    use App\Filament\Pages\ManageGeneralSettings;
    use App\Filament\Pages\ManageIntegrations;
    use App\Filament\Pages\ManageSmsIr;
    use App\Filament\Pages\ManageHomepageImages;
    use App\Filament\Pages\ManageMediaPresets;
    use App\Filament\Pages\ManageTara;
    use App\Filament\Pages\ManageZarinpal;
    use App\Filament\Resources\AttributeResource;
    use App\Filament\Resources\BrandResource;
    use App\Filament\Resources\CategoryResource;
    use App\Filament\Resources\CouponResource;
    use App\Filament\Resources\HomeSliderResource;
    use App\Filament\Resources\MediaFileResource;
    use App\Filament\Resources\MenuItemResource;
    use App\Filament\Resources\OrderResource;
    use App\Filament\Resources\PageResource;
    use App\Filament\Resources\PaymentResource;
    use App\Filament\Resources\PostResource;
    use App\Filament\Resources\ProductResource;
    use App\Filament\Resources\ProductQuestionResource;
    use App\Filament\Resources\ProductReviewResource;
    use App\Filament\Resources\ShippingMethodResource;
    use App\Filament\Resources\UserResource;

    $user = filament()->auth()->user();
    $brand = filament()->getBrandName();
    $isDashboard = request()->routeIs('filament.admin.pages.dashboard');
    $profileUrl = UserResource::getUrl('edit', ['record' => $user->id]);
    $dashboardUrl = filament()->getUrl();

    $panels = [
        'users' => [
            'label' => 'کاربران',
            'icon' => 'fa-users',
            'routes' => ['filament.admin.resources.users.*'],
            'menus' => [
                ['label' => 'کاربران', 'icon' => 'fa-users', 'items' => [
                    ['label' => 'مشتریان', 'url' => UserResource::getUrl('index'), 'icon' => 'fa-user'],
                    ['label' => 'غیر مشتری', 'url' => UserResource::getUrl('index', ['activeTab' => 'staff']), 'icon' => 'fa-user-tie'],
                    ['label' => 'افزودن کاربر', 'url' => UserResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'products' => [
            'label' => 'محصولات',
            'icon' => 'fa-box-open',
            'routes' => [
                'filament.admin.resources.products.*',
                'filament.admin.resources.categories.*',
                'filament.admin.resources.attributes.*',
            ],
            'menus' => [
                ['label' => 'محصولات', 'icon' => 'fa-box-open', 'items' => [
                    ['label' => 'لیست همه', 'url' => ProductResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن محصول', 'url' => ProductResource::getUrl('create'), 'icon' => 'fa-plus'],
                    ['label' => 'دسته‌بندی‌ها', 'url' => CategoryResource::getUrl('index'), 'icon' => 'fa-tags'],
                    ['label' => 'ویژگی‌ها', 'url' => AttributeResource::getUrl('index'), 'icon' => 'fa-list-ul'],
                ]],
            ],
        ],
        'payments' => [
            'label' => 'پرداخت‌ها',
            'icon' => 'fa-credit-card',
            'routes' => ['filament.admin.resources.payments.*'],
            'menus' => [
                ['label' => 'پرداخت‌ها', 'icon' => 'fa-credit-card', 'items' => [
                    ['label' => 'لیست همه', 'url' => PaymentResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
            ],
        ],
        'brands' => [
            'label' => 'برندها',
            'icon' => 'fa-certificate',
            'routes' => ['filament.admin.resources.brands.*'],
            'menus' => [
                ['label' => 'برندها', 'icon' => 'fa-certificate', 'items' => [
                    ['label' => 'لیست همه', 'url' => BrandResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'ایجاد برند', 'url' => BrandResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'discounts' => [
            'label' => 'تخفیف‌ها',
            'icon' => 'fa-percent',
            'routes' => ['filament.admin.resources.coupons.*'],
            'menus' => [
                ['label' => 'تخفیف‌ها', 'icon' => 'fa-percent', 'items' => [
                    ['label' => 'لیست همه', 'url' => CouponResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'ایجاد تخفیف', 'url' => CouponResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'orders' => [
            'label' => 'سفارش‌ها',
            'icon' => 'fa-shopping-bag',
            'routes' => ['filament.admin.resources.orders.*'],
            'menus' => [
                ['label' => 'سفارش‌ها', 'icon' => 'fa-shopping-bag', 'items' => [
                    ['label' => 'لیست همه', 'url' => OrderResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
            ],
        ],
        'content' => [
            'label' => 'محتوا',
            'icon' => 'fa-newspaper',
            'routes' => [
                'filament.admin.resources.posts.*',
                'filament.admin.resources.pages.*',
                'filament.admin.resources.product-reviews.*',
                'filament.admin.resources.product-questions.*',
                'filament.admin.resources.home-sliders.*',
                'filament.admin.resources.menu-items.*',
            ],
            'menus' => [
                ['label' => 'بلاگ', 'icon' => 'fa-file-alt', 'items' => [
                    ['label' => 'لیست همه', 'url' => PostResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'ایجاد مطلب', 'url' => PostResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
                ['label' => 'صفحات', 'icon' => 'fa-file-lines', 'items' => [
                    ['label' => 'لیست همه', 'url' => PageResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'صفحه جدید', 'url' => PageResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
                ['label' => 'نظرات', 'icon' => 'fa-comments', 'items' => [
                    ['label' => 'لیست همه', 'url' => ProductReviewResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
                ['label' => 'پرسش و پاسخ', 'icon' => 'fa-circle-question', 'items' => [
                    ['label' => 'لیست همه', 'url' => ProductQuestionResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
                ['label' => 'اسلایدر', 'icon' => 'fa-images', 'items' => [
                    ['label' => 'لیست همه', 'url' => HomeSliderResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن اسلاید', 'url' => HomeSliderResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
                ['label' => 'منوی سایت', 'icon' => 'fa-bars', 'items' => [
                    ['label' => 'آیتم‌های منو', 'url' => MenuItemResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن آیتم', 'url' => MenuItemResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'shipping' => [
            'label' => 'ارسال',
            'icon' => 'fa-truck',
            'routes' => ['filament.admin.resources.shipping-methods.*'],
            'menus' => [
                ['label' => 'روش‌های ارسال', 'icon' => 'fa-truck', 'items' => [
                    ['label' => 'لیست همه', 'url' => ShippingMethodResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن روش', 'url' => ShippingMethodResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'gateways' => [
            'label' => 'درگاه‌ها',
            'icon' => 'fa-plug',
            'routes' => [
                'filament.admin.pages.zarinpal',
                'filament.admin.pages.tara',
            ],
            'menus' => [
                ['label' => 'درگاه‌های پرداخت', 'icon' => 'fa-plug', 'items' => [
                    ['label' => 'زرین‌پال', 'url' => ManageZarinpal::getUrl(), 'icon' => 'fa-university'],
                    ['label' => 'تارا', 'url' => ManageTara::getUrl(), 'icon' => 'fa-wallet'],
                ]],
            ],
        ],
        'media' => [
            'label' => 'مدیریت فایل',
            'icon' => 'fa-folder-open',
            'routes' => [
                'filament.admin.resources.media-files.*',
                'filament.admin.pages.media-presets',
                'filament.admin.pages.homepage-images',
            ],
            'menus' => [
                ['label' => 'کتابخانه رسانه', 'icon' => 'fa-photo-film', 'items' => [
                    ['label' => 'همه فایل‌ها', 'url' => MediaFileResource::getUrl('index'), 'icon' => 'fa-images'],
                ]],
                ['label' => 'تنظیمات تصویر', 'icon' => 'fa-crop', 'items' => [
                    ['label' => 'سایز آپلود', 'url' => ManageMediaPresets::getUrl(), 'icon' => 'fa-sliders'],
                    ['label' => 'تامبنیل صفحه اصلی', 'url' => ManageHomepageImages::getUrl(), 'icon' => 'fa-image'],
                ]],
            ],
        ],
        'settings' => [
            'label' => 'تنظیمات',
            'icon' => 'fa-cog',
            'routes' => [
                'filament.admin.pages.manage-general-settings',
                'filament.admin.pages.kavenegar',
                'filament.admin.pages.sms-ir',
            ],
            'menus' => [
                ['label' => 'تنظیمات عمومی', 'icon' => 'fa-globe', 'items' => [
                    ['label' => 'مدیریت سایت', 'url' => ManageGeneralSettings::getUrl(), 'icon' => 'fa-cog'],
                ]],
                ['label' => 'پیامک', 'icon' => 'fa-sms', 'items' => [
                    ['label' => 'sms.ir', 'url' => ManageSmsIr::getUrl(), 'icon' => 'fa-comment-dots'],
                    ['label' => 'کاوه‌نگار', 'url' => ManageIntegrations::getUrl(), 'icon' => 'fa-comment-sms'],
                ]],
            ],
        ],
    ];

    $activePanel = 'users';
    $sidebarPanel = 'users';
    if ($isDashboard) {
        $activePanel = 'dashboard';
        $sidebarPanel = 'users';
    } else {
        foreach ($panels as $panelId => $panel) {
            if (collect($panel['routes'])->contains(fn ($p) => request()->routeIs($p))) {
                $activePanel = $panelId;
                $sidebarPanel = $panelId;
                break;
            }
        }
    }

    $activePanelLabel = $isDashboard ? 'داشبورد' : ($panels[$sidebarPanel]['label'] ?? 'منو');

    $navIcons = [
        ['id' => 'dashboard', 'icon' => 'fa-chart-pie', 'tooltip' => 'داشبورد', 'href' => $dashboardUrl, 'isLink' => true],
        ['id' => 'users', 'icon' => 'fa-users', 'tooltip' => 'کاربران', 'panel' => 'users'],
        ['id' => 'products', 'icon' => 'fa-box-open', 'tooltip' => 'محصولات', 'panel' => 'products'],
        ['id' => 'payments', 'icon' => 'fa-credit-card', 'tooltip' => 'پرداخت‌ها', 'panel' => 'payments'],
        ['id' => 'brands', 'icon' => 'fa-certificate', 'tooltip' => 'برندها', 'panel' => 'brands'],
        ['id' => 'discounts', 'icon' => 'fa-percent', 'tooltip' => 'تخفیف‌ها', 'panel' => 'discounts'],
        ['id' => 'orders', 'icon' => 'fa-shopping-bag', 'tooltip' => 'سفارش‌ها', 'panel' => 'orders'],
        ['id' => 'content', 'icon' => 'fa-newspaper', 'tooltip' => 'محتوا', 'panel' => 'content'],
        ['id' => 'shipping', 'icon' => 'fa-truck', 'tooltip' => 'ارسال', 'panel' => 'shipping'],
        ['id' => 'gateways', 'icon' => 'fa-plug', 'tooltip' => 'درگاه‌ها', 'panel' => 'gateways'],
        ['id' => 'media', 'icon' => 'fa-folder-open', 'tooltip' => 'مدیریت فایل', 'panel' => 'media'],
        ['id' => 'settings', 'icon' => 'fa-cog', 'tooltip' => 'تنظیمات', 'panel' => 'settings'],
    ];

    $isMenuItemActive = function (string $url): bool {
        $current = url()->current();
        $target = rtrim($url, '/');

        return $current === $target || str_starts_with($current, $target.'/');
    };

    $initial = mb_substr($user?->name ?? 'ا', 0, 1);
@endphp

<div class="sidebar-secondary">
    @foreach ($navIcons as $navIcon)
        @if ($navIcon['isLink'] ?? false)
            <a href="{{ $navIcon['href'] }}"
               class="sidebar-icon-item sidebar-icon-link {{ $activePanel === 'dashboard' ? 'active' : '' }}"
               data-tooltip="{{ $navIcon['tooltip'] }}"
               aria-label="{{ $navIcon['tooltip'] }}">
                <i class="fas {{ $navIcon['icon'] }}"></i>
            </a>
        @else
            <div class="sidebar-icon-item {{ $activePanel === $navIcon['id'] ? 'active' : '' }}"
                 data-panel="{{ $navIcon['panel'] }}"
                 data-tooltip="{{ $navIcon['tooltip'] }}"
                 role="button"
                 tabindex="0"
                 aria-label="{{ $navIcon['tooltip'] }}">
                <i class="fas {{ $navIcon['icon'] }}"></i>
            </div>
        @endif
        @if ($loop->first)
            <div class="sidebar-divider"></div>
        @endif
    @endforeach

    <a href="{{ $profileUrl }}"
       class="sidebar-user-icon d-flex align-items-center justify-content-center text-white fw-bold text-decoration-none"
       style="background: linear-gradient(135deg, #7239ea 0%, #9d5cff 100%); font-size: 14px;"
       data-tooltip="پروفایل: {{ $user?->name ?? 'کاربر' }}"
       aria-label="پروفایل کاربر">{{ $initial }}</a>
</div>

<div class="sidebar-primary" id="sidebarPrimary">
    <div class="sidebar-header">
        <p class="sidebar-title">منوی اصلی</p>
        <p class="mb-0 fw-bold text-dark" id="sidebarPanelTitle">{{ $activePanelLabel }}</p>
    </div>

    <div class="sidebar-menu" id="sidebarMenu">
        @foreach ($panels as $panelId => $panel)
            <div class="menu-content {{ $sidebarPanel === $panelId ? 'active' : '' }}" id="{{ $panelId }}Content">
                @foreach ($panel['menus'] as $menuIndex => $menu)
                    @php
                        $submenuId = $panelId.'Submenu'.$menuIndex;
                        $hasActiveChild = collect($menu['items'])->contains(fn ($item) => $isMenuItemActive($item['url']));
                    @endphp
                    <button type="button"
                            class="menu-item {{ $hasActiveChild ? 'active' : '' }}"
                            data-submenu="{{ $submenuId }}"
                            aria-expanded="{{ $hasActiveChild ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="menu-icon"><i class="fas {{ $menu['icon'] }}"></i></span>
                            <span>{{ $menu['label'] }}</span>
                        </div>
                        <i class="fas fa-chevron-down menu-arrow" style="transform: rotate({{ $hasActiveChild ? '180' : '0' }}deg);"></i>
                    </button>
                    <div class="submenu {{ $hasActiveChild ? 'expanded' : '' }}" id="{{ $submenuId }}">
                        @foreach ($menu['items'] as $item)
                            <a href="{{ $item['url'] }}"
                               class="submenu-item {{ $isMenuItemActive($item['url']) ? 'active' : '' }}">
                                <i class="fas {{ $item['icon'] }} ms-2 submenu-item-icon"></i>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

<div class="toggle-sidebar-btn" onclick="toggleSidebar()">
    <i class="fas fa-chevron-left" id="toggleIcon"></i>
</div>

<script>
    window.__adminPanelTitles = @json(collect($panels)->mapWithKeys(fn ($p, $id) => [$id => $p['label']]));
</script>
