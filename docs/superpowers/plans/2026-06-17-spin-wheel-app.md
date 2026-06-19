# Spin Wheel App Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Laravel + SQLite spin-wheel app with one shared admin area for managing multiple campaigns and one public QR-driven play page per campaign.

**Architecture:** Use a single Laravel 12 application with Blade views, Alpine.js for lightweight interaction, and SQLite for persistence. Keep admin and public pages server-rendered, expose a JSON spin endpoint for the wheel animation, and store only the latest result per campaign to enforce the no-immediate-repeat rule.

**Tech Stack:** Laravel, PHP, SQLite, Blade, Alpine.js, Vite, Pest, Simple QrCode

---

## File Structure

- `composer.json`: Laravel app dependencies plus `simplesoftwareio/simple-qrcode`
- `package.json`: Frontend build dependencies used by Vite and Alpine
- `.env.example`: SQLite path, admin password env var, app URL defaults
- `database/migrations/*.php`: `campaigns` and `campaign_items` tables
- `app/Models/Campaign.php`: Campaign relations and ready-state helpers
- `app/Models/CampaignItem.php`: Item model and ordering helpers
- `database/factories/CampaignFactory.php`: Test factory for campaigns
- `database/factories/CampaignItemFactory.php`: Test factory for items
- `app/Http/Middleware/AdminAuthenticated.php`: Session gate for admin area
- `app/Http/Controllers/Admin/AuthController.php`: Minimal admin login/logout
- `app/Http/Controllers/Admin/CampaignController.php`: Admin CRUD
- `app/Http/Controllers/PublicCampaignController.php`: Public play screen and spin endpoint
- `app/Services/SpinResultPicker.php`: Encapsulated winner selection rule
- `resources/views/layouts/app.blade.php`: Base layout and asset loading
- `resources/views/admin/**/*.blade.php`: Admin login, list, form
- `resources/views/play/show.blade.php`: Mobile play page based on `quayso` assets
- `resources/js/app.js`: Alpine bootstrap and public wheel controller registration
- `resources/css/app.css`: App theme, layout, wheel, ticket styling, mobile adjustments
- `public/layout-assets/*`: Published copies of images from `quayso/`
- `routes/web.php`: Admin and public routes
- `tests/Feature/Admin/*.php`: Admin flow coverage
- `tests/Feature/Play/*.php`: Public play and spin rule coverage

### Task 1: Bootstrap the Laravel application and local tooling

**Files:**
- Create: `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `tests/`
- Modify: `composer.json`, `package.json`, `.env.example`, `vite.config.js`
- Test: `tests/Feature/ExampleTest.php`

- [ ] **Step 1: Scaffold the base Laravel app**

Run:

```bash
composer create-project laravel/laravel .
composer require simplesoftwareio/simple-qrcode
npm install
```

Expected: Laravel app files exist in the repo root and `composer.json` includes the QR package.

- [ ] **Step 2: Configure SQLite and admin env defaults**

Update `.env.example` so the new app starts with SQLite and a shared admin password.

```dotenv
APP_NAME="Spin Wheel"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# DB_DATABASE is resolved relative to the app root by the bootstrap script.
DB_DATABASE=database/database.sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

ADMIN_USERNAME=admin
ADMIN_PASSWORD=change-me
```

- [ ] **Step 3: Replace the example test with a smoke test for the welcome redirect target**

Use a minimal passing test so the suite has a stable starting point.

```php
<?php

test('the application responds on the root path', function () {
    $this->get('/')->assertStatus(200);
});
```

- [ ] **Step 4: Run the base test suite**

Run: `php artisan test tests/Feature/ExampleTest.php`

Expected: PASS with `1 passed`.

- [ ] **Step 5: Commit**

```bash
git add .
git commit -m "chore: bootstrap laravel spin wheel app"
```

### Task 2: Add persistence and admin access guard

**Files:**
- Create: `database/migrations/2026_06_17_000000_create_campaigns_table.php`
- Create: `database/migrations/2026_06_17_000001_create_campaign_items_table.php`
- Create: `app/Models/Campaign.php`
- Create: `app/Models/CampaignItem.php`
- Create: `app/Http/Middleware/AdminAuthenticated.php`
- Create: `app/Http/Controllers/Admin/AuthController.php`
- Modify: `bootstrap/app.php`, `routes/web.php`
- Test: `tests/Feature/Admin/AdminAuthTest.php`

- [ ] **Step 1: Write the failing admin auth test**

```php
<?php

it('redirects guests away from admin campaigns', function () {
    $this->get('/admin/campaigns')->assertRedirect('/admin/login');
});

it('logs in with the configured shared credentials', function () {
    config()->set('app.admin_username', 'admin');
    config()->set('app.admin_password', 'secret');

    $this->post('/admin/login', [
        'username' => 'admin',
        'password' => 'secret',
    ])->assertRedirect('/admin/campaigns');

    $this->get('/admin/campaigns')->assertOk();
});
```

- [ ] **Step 2: Run the auth test to verify it fails**

Run: `php artisan test tests/Feature/Admin/AdminAuthTest.php`

Expected: FAIL because `/admin/login` and the middleware do not exist yet.

- [ ] **Step 3: Implement the database schema, models, and admin login guard**

Create the core migration shape.

```php
Schema::create('campaigns', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('public_token')->unique();
    $table->boolean('is_active')->default(false);
    $table->foreignId('last_result_item_id')->nullable()->constrained('campaign_items')->nullOnDelete();
    $table->timestamps();
});
```

```php
Schema::create('campaign_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
    $table->string('label');
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

Create the model essentials.

```php
class Campaign extends Model
{
    protected $fillable = ['name', 'slug', 'public_token', 'is_active', 'last_result_item_id'];
    protected $casts = ['is_active' => 'bool'];

    public function items(): HasMany { return $this->hasMany(CampaignItem::class)->orderBy('sort_order'); }
    public function lastResultItem(): BelongsTo { return $this->belongsTo(CampaignItem::class, 'last_result_item_id'); }
}
```

```php
class CampaignItem extends Model
{
    protected $fillable = ['campaign_id', 'label', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'bool'];

    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }
}
```

Create a simple session-based middleware and auth controller.

```php
class AdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('admin_authenticated')) {
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
```

```php
class AuthController extends Controller
{
    public function show(): View { return view('admin.auth.login'); }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['username' => ['required'], 'password' => ['required']]);

        if ($credentials['username'] !== env('ADMIN_USERNAME') || $credentials['password'] !== env('ADMIN_PASSWORD')) {
            return back()->withErrors(['username' => 'Invalid credentials.'])->onlyInput('username');
        }

        $request->session()->put('admin_authenticated', true);

        return redirect('/admin/campaigns');
    }
}
```

Wire the middleware alias in `bootstrap/app.php` and add placeholder admin routes in `routes/web.php`.

- [ ] **Step 4: Run migrations and the auth test again**

Run:

```bash
touch database/database.sqlite
php artisan migrate
php artisan test tests/Feature/Admin/AdminAuthTest.php
```

Expected: PASS with `2 passed`.

- [ ] **Step 5: Commit**

```bash
git add app bootstrap database routes tests
git commit -m "feat: add campaign schema and admin login gate"
```

### Task 3: Build campaign CRUD and item management

**Files:**
- Create: `app/Http/Controllers/Admin/CampaignController.php`
- Create: `resources/views/admin/auth/login.blade.php`
- Create: `resources/views/admin/campaigns/index.blade.php`
- Create: `resources/views/admin/campaigns/form.blade.php`
- Test: `tests/Feature/Admin/CampaignManagementTest.php`

- [ ] **Step 1: Write the failing campaign management test**

```php
<?php

use App\Models\Campaign;

beforeEach(function () {
    session(['admin_authenticated' => true]);
});

it('creates a campaign with ordered items', function () {
    $response = $this->post('/admin/campaigns', [
        'name' => 'Changg Anniversary',
        'is_active' => '1',
        'items' => [
            ['label' => 'Voucher 10%', 'is_active' => '1'],
            ['label' => 'Free Americano', 'is_active' => '1'],
        ],
    ]);

    $response->assertRedirect();

    $campaign = Campaign::query()->where('name', 'Changg Anniversary')->firstOrFail();

    expect($campaign->items()->pluck('label')->all())->toBe(['Voucher 10%', 'Free Americano']);
});
```

- [ ] **Step 2: Run the campaign management test to verify it fails**

Run: `php artisan test tests/Feature/Admin/CampaignManagementTest.php`

Expected: FAIL because the controller and views do not exist yet.

- [ ] **Step 3: Implement admin CRUD and item persistence**

Key controller behavior:

```php
public function store(Request $request): RedirectResponse
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'is_active' => ['nullable', 'boolean'],
        'items' => ['array'],
        'items.*.label' => ['required', 'string', 'max:255'],
        'items.*.is_active' => ['nullable', 'boolean'],
    ]);

    $campaign = Campaign::create([
        'name' => $data['name'],
        'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(6)),
        'public_token' => Str::random(32),
        'is_active' => (bool) ($data['is_active'] ?? false),
    ]);

    collect($data['items'] ?? [])->values()->each(function (array $item, int $index) use ($campaign) {
        $campaign->items()->create([
            'label' => $item['label'],
            'sort_order' => $index,
            'is_active' => (bool) ($item['is_active'] ?? false),
        ]);
    });

    return redirect()->route('admin.campaigns.edit', $campaign)->with('status', 'Campaign saved.');
}
```

The form view should render repeatable item rows with Alpine.js so staff can add and remove items without a page reload.

- [ ] **Step 4: Run the feature test**

Run: `php artisan test tests/Feature/Admin/CampaignManagementTest.php`

Expected: PASS with `1 passed`.

- [ ] **Step 5: Commit**

```bash
git add app/resources/routes tests
git commit -m "feat: add admin campaign management"
```

### Task 4: Implement the spin selection service and public routes

**Files:**
- Create: `app/Services/SpinResultPicker.php`
- Create: `app/Http/Controllers/PublicCampaignController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Play/SpinResultTest.php`

- [ ] **Step 1: Write the failing public spin tests**

```php
<?php

use App\Models\Campaign;
use App\Models\CampaignItem;

it('does not repeat the immediately previous result when two items are active', function () {
    $campaign = Campaign::factory()->create();
    $first = CampaignItem::factory()->for($campaign)->create(['label' => 'A', 'sort_order' => 0]);
    CampaignItem::factory()->for($campaign)->create(['label' => 'B', 'sort_order' => 1]);

    $campaign->update(['last_result_item_id' => $first->id]);

    $this->postJson("/play/{$campaign->public_token}/spin")
        ->assertOk()
        ->assertJsonPath('result.label', 'B');
});

it('returns the only active item when the campaign has one option', function () {
    $campaign = Campaign::factory()->create();
    CampaignItem::factory()->for($campaign)->create(['label' => 'Only prize']);

    $this->postJson("/play/{$campaign->public_token}/spin")
        ->assertOk()
        ->assertJsonPath('result.label', 'Only prize');
});
```

- [ ] **Step 2: Run the spin tests to verify they fail**

Run: `php artisan test tests/Feature/Play/SpinResultTest.php`

Expected: FAIL because the factories, routes, and spin service do not exist yet.

- [ ] **Step 3: Implement factories, the spin service, and the public controller**

Service contract:

```php
class SpinResultPicker
{
    public function pick(Campaign $campaign): CampaignItem
    {
        return DB::transaction(function () use ($campaign) {
            $campaign = Campaign::query()->with('items')->findOrFail($campaign->id);

            $activeItems = $campaign->items->where('is_active', true)->values();

            abort_if($activeItems->isEmpty(), 422, 'This campaign has no active items.');

            if ($activeItems->count() === 1) {
                $selected = $activeItems->first();
            } else {
                $selected = $activeItems
                    ->reject(fn (CampaignItem $item) => $item->id === $campaign->last_result_item_id)
                    ->values()
                    ->random();
            }

            $campaign->forceFill(['last_result_item_id' => $selected->id])->save();

            return $selected;
        });
    }
}
```

Public controller contract:

```php
public function show(string $token): View
{
    $campaign = Campaign::query()->with(['items' => fn ($query) => $query->where('is_active', true)])
        ->where('public_token', $token)
        ->where('is_active', true)
        ->firstOrFail();

    return view('play.show', ['campaign' => $campaign]);
}

public function spin(string $token, SpinResultPicker $picker): JsonResponse
{
    $campaign = Campaign::query()->where('public_token', $token)->where('is_active', true)->firstOrFail();
    $result = $picker->pick($campaign);

    return response()->json(['result' => ['id' => $result->id, 'label' => $result->label]]);
}
```

- [ ] **Step 4: Run the play tests**

Run: `php artisan test tests/Feature/Play/SpinResultTest.php`

Expected: PASS with `2 passed`.

- [ ] **Step 5: Commit**

```bash
git add app database routes tests
git commit -m "feat: add public spin flow and repeat prevention"
```

### Task 5: Build the public mobile layout from the provided assets

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/play/show.blade.php`
- Create: `resources/js/app.js`
- Create: `resources/css/app.css`
- Create: `public/layout-assets/changg-logo.png`
- Create: `public/layout-assets/mascot.png`
- Create: `public/layout-assets/doodle-left.png`
- Create: `public/layout-assets/doodle-right.png`
- Create: `public/layout-assets/wave-lines.png`
- Create: `public/layout-assets/ticket-bg.png`
- Test: `tests/Feature/Play/PublicPlayPageTest.php`

- [ ] **Step 1: Write the failing public page test**

```php
<?php

it('renders the branded play page for an active campaign', function () {
    $campaign = Campaign::factory()->active()->create(['name' => '2 Years Anniversary']);
    CampaignItem::factory()->count(6)->for($campaign)->create();

    $this->get("/play/{$campaign->public_token}")
        ->assertOk()
        ->assertSee('QUAY NGAY!', false)
        ->assertSee('2 YEARS', false);
});
```

- [ ] **Step 2: Run the public page test to verify it fails**

Run: `php artisan test tests/Feature/Play/PublicPlayPageTest.php`

Expected: FAIL because the branded Blade view and assets are not present yet.

- [ ] **Step 3: Implement the branded Blade view and Alpine wheel controller**

Copy the source images from `quayso/` into `public/layout-assets/` using these names:

```text
quayso/1.png -> public/layout-assets/mascot.png
quayso/2.png -> public/layout-assets/changg-logo.png
quayso/3.png -> public/layout-assets/doodle-left.png
quayso/4.png -> public/layout-assets/wave-lines.png
quayso/5.png -> public/layout-assets/doodle-right.png
quayso/6.png -> public/layout-assets/ticket-bg.png
```

Build the page around a Blade structure like this:

```blade
<section class="play-hero">
    <img class="play-logo" src="{{ asset('layout-assets/changg-logo.png') }}" alt="Changg Cafe Slowbar">
    <div class="play-title-wrap">
        <h1 class="play-title">2 YEARS</h1>
        <p class="play-subtitle">ANNIVERSARY</p>
    </div>
    <img class="play-mascot" src="{{ asset('layout-assets/mascot.png') }}" alt="Mascot">
</section>

<section x-data="spinWheel(@js($campaign->items->map(fn ($item) => ['id' => $item->id, 'label' => $item->label])->values()), '{{ route('play.spin', $campaign->public_token) }}')" class="play-wheel-section">
    <div class="wheel-shell">
        <button type="button" class="wheel-pointer" aria-hidden="true"></button>
        <div class="wheel-disc" :style="wheelStyle"></div>
    </div>

    <button class="spin-button" :disabled="spinning" @click="spin">QUAY NGAY!</button>

    <div class="result-ticket" x-show="resultLabel" x-cloak>
        <div class="result-ticket__inner">
            <p class="result-ticket__caption">CHUC MUNG BAN NHAN DUOC</p>
            <p class="result-ticket__value" x-text="resultLabel"></p>
        </div>
    </div>
</section>
```

Use an Alpine controller that stores `segments`, `resultLabel`, `rotation`, `spinning`, computes a CSS conic-gradient wheel, posts to `/play/{token}/spin`, then rotates to the winning segment.

- [ ] **Step 4: Run the public page test and a production build**

Run:

```bash
php artisan test tests/Feature/Play/PublicPlayPageTest.php
npm run build
```

Expected: the test passes and Vite completes a production build successfully.

- [ ] **Step 5: Commit**

```bash
git add public resources tests
git commit -m "feat: add branded mobile spin wheel page"
```

### Task 6: Finish admin UX with QR code display and edit flow

**Files:**
- Modify: `app/Http/Controllers/Admin/CampaignController.php`
- Modify: `resources/views/admin/campaigns/index.blade.php`
- Modify: `resources/views/admin/campaigns/form.blade.php`
- Test: `tests/Feature/Admin/CampaignQrDisplayTest.php`

- [ ] **Step 1: Write the failing QR display test**

```php
<?php

it('shows the public URL and inline QR code on the campaign edit page', function () {
    session(['admin_authenticated' => true]);

    $campaign = Campaign::factory()->create(['public_token' => 'public-token-123']);

    $this->get(route('admin.campaigns.edit', $campaign))
        ->assertOk()
        ->assertSee(route('play.show', $campaign->public_token), false)
        ->assertSee('<svg', false);
});
```

- [ ] **Step 2: Run the QR test to verify it fails**

Run: `php artisan test tests/Feature/Admin/CampaignQrDisplayTest.php`

Expected: FAIL because the edit page does not render the QR block yet.

- [ ] **Step 3: Implement the edit view QR panel and update flow**

Render the URL and QR code inline from Blade.

```blade
@php($publicUrl = route('play.show', $campaign->public_token))

<div class="admin-panel">
    <p class="admin-label">Public URL</p>
    <input class="admin-input" type="text" readonly value="{{ $publicUrl }}" onclick="this.select()">
    <div class="admin-qr">{!! QrCode::size(180)->generate($publicUrl) !!}</div>
</div>
```

Make sure `update()` replaces item rows from the submitted form so staff edits take effect immediately on later spins.

- [ ] **Step 4: Run the targeted admin suite**

Run: `php artisan test tests/Feature/Admin`

Expected: PASS for auth, CRUD, and QR coverage.

- [ ] **Step 5: Commit**

```bash
git add app resources tests
git commit -m "feat: show campaign qr codes in admin"
```

### Task 7: Full verification and deployment notes

**Files:**
- Create: `README.md`
- Modify: `docs/superpowers/specs/2026-06-15-spin-wheel-design.md` only if implementation changed scope
- Test: full suite and manual smoke checklist

- [ ] **Step 1: Write the README for setup and deployment**

Include:

```md
# Spin Wheel App

## Setup
- `cp .env.example .env`
- `php artisan key:generate`
- `touch database/database.sqlite`
- `php artisan migrate`
- `npm install && npm run build`

## Admin login
- Username from `ADMIN_USERNAME`
- Password from `ADMIN_PASSWORD`

## Deploy
- Point the web root at `public/`
- Ensure `storage/` and `bootstrap/cache/` are writable
- Run migrations during deploy
```

- [ ] **Step 2: Run the full automated checks**

Run:

```bash
php artisan test
npm run build
php artisan route:list
```

Expected: tests pass, the asset build passes, and routes include `admin.login`, `admin.campaigns.*`, `play.show`, and `play.spin`.

- [ ] **Step 3: Manual smoke test locally**

Run:

```bash
php artisan serve
```

Verify manually:
- `/admin/login` accepts the configured credentials.
- Creating a campaign with at least 2 items works.
- The edit page shows the public URL and QR code.
- `/play/{token}` renders correctly on a narrow viewport.
- Two consecutive spins do not return the same label when at least 2 items are active.
- If the item list is reduced to 1 active item, the same item can still be returned.

- [ ] **Step 4: Commit the final docs and polish**

```bash
git add README.md
git commit -m "docs: add spin wheel setup and deploy notes"
```

## Self-Review

- Spec coverage: the plan covers admin multi-campaign CRUD, QR generation, public mobile page, no-immediate-repeat rule, SQLite persistence, and deployment notes.
- Placeholder scan: no `TODO`, `TBD`, or cross-task placeholders remain.
- Type consistency: `Campaign`, `CampaignItem`, `SpinResultPicker`, `play.show`, and `play.spin` are named consistently across tasks.
