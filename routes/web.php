<?php

use App\Http\Controllers\Api\IdentityController;
use App\Http\Controllers\Auth\DiscordAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Items\BrandController;
use App\Http\Controllers\Items\CategoryController;
use App\Http\Controllers\Items\ColorController;
use App\Http\Controllers\Items\FeatureController;
use App\Http\Controllers\Items\ItemHistoryController;
use App\Http\Controllers\Items\ItemController;
use App\Http\Controllers\Items\ItemCandidateEditController;
use App\Http\Controllers\Items\SubmitShoeController;
use App\Http\Controllers\Items\TagController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::get('auth/discord/redirect', [DiscordAuthController::class, 'redirect'])->name('auth.discord.redirect');
    Route::get('auth/discord/callback', [DiscordAuthController::class, 'callback'])->name('auth.discord.callback');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Homepage
Route::get('/', [HomeController::class, 'homepage'])->name('home');
Route::get('/lang', [HomeController::class, 'set_lang'])->name('set_lang');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::post('/search', [SearchController::class, 'post'])->name('search_post');

// User
Route::prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'profile'])->name('profile');
    Route::post('/', [ProfileController::class, 'update'])->name('update');
    Route::get('closet', [ProfileController::class, 'closet'])->name('closet');
    Route::get('{username}/closet', [PublicProfileController::class, 'closet'])->name('public_closet');
    Route::get('wishlist', [ProfileController::class, 'wishlist'])->name('wishlist');
    Route::get('{username}/wishlist', [PublicProfileController::class, 'wishlist'])->name('public_wishlist');
});

// auth endpoint (for mediawiki)
Route::get('api/auth', [IdentityController::class, 'show']);

// blog posts route.
Route::get('blog/{post}', [BlogController::class, 'show'])->name('posts.show');

// categories/brands/features etc
$options = ['only' => ['show', 'index']];

Route::resource('brands', BrandController::class, $options);
Route::resource('categories', CategoryController::class, $options);
Route::resource('features', FeatureController::class, $options);
Route::resource('colors', ColorController::class, $options);
Route::resource('tags', TagController::class, $options);

Route::get('items', [ItemController::class, 'index'])->name('items.index');
Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('items/{item}/history', [ItemHistoryController::class, 'show'])->name('items.history');
Route::get('candidate-edits', [ItemCandidateEditController::class, 'index'])->name('candidate-edits.index');
Route::get('candidate-edits/{candidateEdit}', [ItemCandidateEditController::class, 'show'])->name('candidate-edits.show');

Route::get('donate', [DonationController::class, 'index'])->name('donate');
Route::get('donate/thanks', [DonationController::class, 'thanks'])->name('donate.thanks');
Route::get('donate/paypal', [DonationController::class, 'paypal'])->name('donate.paypal');
Route::get('donate/patreon', [DonationController::class, 'patreon'])->name('donate.patreon');

Route::group(['middleware' => ['auth']], function () {
    Route::put('items/{item}/closet', [ItemController::class, 'closet'])->name('items.closet');
    Route::put('items/{item}/wishlist', [ItemController::class, 'wishlist'])->name('items.wishlist');
});

Route::middleware('auth')->group(function () {
    Route::get('submissions', [SubmitShoeController::class, 'index'])->name('submit.index');
    Route::get('submit', [SubmitShoeController::class, 'create'])->name('submit.create');
    Route::post('submit', [SubmitShoeController::class, 'store'])->name('submit.store');
    Route::get('submit/{item}/thanks', [SubmitShoeController::class, 'thanks'])->name('submit.thanks');
    Route::get('items/{item}/candidate-edits/create', [ItemCandidateEditController::class, 'create'])->name('items.candidate-edits.create');
    Route::post('items/{item}/candidate-edits', [ItemCandidateEditController::class, 'store'])->name('items.candidate-edits.store');
    Route::post('candidate-edits/{candidateEdit}/vote', [ItemCandidateEditController::class, 'vote'])->name('candidate-edits.vote');
    Route::post('candidate-edits/{candidateEdit}/apply', [ItemCandidateEditController::class, 'apply'])->name('candidate-edits.apply');
    Route::post('candidate-edits/{candidateEdit}/reject', [ItemCandidateEditController::class, 'reject'])->name('candidate-edits.reject');
});
