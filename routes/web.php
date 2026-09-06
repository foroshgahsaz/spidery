<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartAddController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Account\AccountDashboard;
use App\Livewire\Account\AddressManager;
use App\Livewire\Account\OrderList;
use App\Livewire\Account\OrderShow;
use App\Livewire\Account\PaymentList;
use App\Livewire\Account\ProfilePage;
use App\Livewire\Account\WishlistPage;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Cart\CartPage;
use App\Livewire\Checkout\CheckoutPage;
use Illuminate\Support\Facades\Route;

Route::get('/data/{path}', function (string $path) {
    $path = ltrim($path, '/');

    if ($path === '' || str_contains($path, '..')) {
        abort(404);
    }

    $disk = \Illuminate\Support\Facades\Storage::disk('public');

    if (! $disk->exists($path)) {
        abort(404);
    }

    return response()->file($disk->path($path));
})->where('path', '.*')->name('filesystem.public');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/authors/{author:slug}', [AuthorController::class, 'show'])->name('authors.show');

Route::get('/product/{slug}', fn (string $slug) => redirect("/products/{$slug}", 301));

Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /account',
        'Disallow: /checkout',
        'Disallow: /cart',
        'Disallow: /payment/',
        'Disallow: /login',
        'Disallow: /register',
        '',
        'Sitemap: '.url('/sitemap.xml'),
    ];

    return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

Route::post('/cart/add', CartAddController::class)->name('cart.add');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::get('/cart', CartPage::class)->name('cart');
Route::get('/checkout', CheckoutPage::class)->name('checkout')->middleware('auth');
Route::match(['get', 'post'], '/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::match(['get', 'post'], '/payment/callback/tara', [PaymentController::class, 'taraCallback'])->name('payment.callback.tara');
Route::get('/payment/tara/{tracking}', [PaymentController::class, 'taraRedirect'])->name('payment.tara.redirect');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', AccountDashboard::class)->name('dashboard');
    Route::get('/orders', OrderList::class)->name('orders');
    Route::get('/orders/{order}', OrderShow::class)->name('orders.show');
    Route::get('/payments', PaymentList::class)->name('payments');
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::get('/addresses', AddressManager::class)->name('addresses');
    Route::get('/wishlist', WishlistPage::class)->name('wishlist');
});
