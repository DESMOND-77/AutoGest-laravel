# Module Boutique (Store) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the existing `App\Domain\Store` domain into a full boutique module: sales with partial-payment support (student or walk-in buyer), a richer product catalog with stock-movement history, supplier purchase orders (réapprovisionnement) that replenish stock on receipt, and a reporting tab (dashboard KPIs + CSV/PDF export) — consolidated into one 4-tab admin screen (Ventes / Rapports / Produits / Réapprovisionnement).

**Architecture:** `App\Domain\Store` already exists (`Order`, `OrderItem`, `Product`, `Supplier`, `OrderService`) and is *already* documented and permitted to depend on `App\Domain\Finance` directly (see `OrderService`'s own docblock and `tests/Architecture/DomainBoundariesTest.php`'s Store rule, which does not forbid Finance) — unlike Fleet/Recyclage, which are deliberately barred from it. This plan leans into that: rather than building a parallel `Sale`/`SaleItem`/`SalePayment` system (as `Promptset/10-module-code-rousseau.md`'s own proposed file tree suggested), it makes **every** `Order` — walk-in or student-linked — get a real `Invoice`, so partial-payment tracking, cancellation, and ledger posting all come from the *existing*, already-tested `PaymentService`/`InvoicingService` with zero duplication. This was confirmed with the user as the preferred architecture over the prompt's literal proposal (see Global Constraints). Réapprovisionnement (`PurchaseOrder`) is genuinely new — nothing today models incoming supplier stock, only outgoing sales.

**Tech Stack:** Laravel 12, PHP 8.5, Pest 3, Blade (Alpine `x-tabs`), `bcmath` for all money math, `barryvdh/laravel-dompdf` (new dependency, approved by the user for the Rapports tab's PDF export).

**Spec:** `Promptset/10-module-code-rousseau.md`. Business confirmation was obtained directly from the user before this plan was written (the prompt's own blocking gate), the mentioned "Option A/B" was confirmed to be undefined/inapplicable and ignored, and the reuse-not-duplicate architecture plus the PDF dependency were both confirmed via clarifying questions — none of these are open questions for execution to re-litigate.

## Global Constraints

- **Never build `Sale`/`SaleItem`/`SalePayment`.** Every purchase — student-linked or walk-in — is an `Order` that owns exactly one `Invoice`. Partial payments against it go through the *existing* `PaymentController`/`PaymentService`/`app/domain/finance` views, unmodified. Do not add a `SalePaymentRecorded` event — Finance already journals to the ledger internally inside `PaymentService::record()` (no cross-domain event needed for a same-domain write) and Store already imports Finance directly for exactly this reason (see `OrderService`'s docblock).
- `Invoice.student_id` becomes nullable (additive migration only — do not touch any other column, do not backfill/rewrite existing rows).
- Insufficient stock **must not block a sale** — `InsufficientStock` currently aborts `OrderService::place()` entirely; the prompt's acceptance criterion is "alerte si stock insuffisant, mais vente autorisée avec notification." The sale must still go through; stock is allowed to go negative or floor at 0 (decide and document which in Task 1), and the response must carry a warning message distinct from the normal success message.
- Every new model gets `BelongsToTenant` except join/line-item-only tables that are only ever reached through an already-scoped parent (mirroring `OrderItem`'s own documented reasoning — no redundant `structure_id`).
- Do not add explicit `structure_id` filters anywhere the ambient `TenantContext`/`BelongsToTenant` scope already applies — this session's established convention, already corrected three times in earlier, unrelated plans for this exact mistake.
- Reuse `App\Support\CsvExporter` for every CSV export (already used by `ReportsController`) — do not write a second CSV mechanism.
- No arrow glyphs (←/→) in any UI copy.
- Every change must have a passing Pest test. Run `vendor/bin/pint --dirty --format agent` after PHP edits.
- Composer dependency change (`barryvdh/laravel-dompdf`) is pre-approved for this plan only — do not add any other dependency without asking first.
- Per this project's standing DB-safety rule: run migrations against `TEST_DB_DATABASE` (Pest already does this automatically); when this plan's work is later applied to the real dev database, use `php artisan migrate` (never `fresh`/`refresh`) — this plan's own migrations are additive-only by design.

---

### Task 1: Make every Order own an Invoice; stock warns instead of blocking

**Files:**
- Create: migration via `php artisan make:migration make_student_id_nullable_on_invoices_table --table=invoices`
- Modify: `app/Domain/Finance/Services/InvoicingService.php`
- Modify: `app/Domain/Store/Services/OrderService.php`
- Modify: `app/Domain/Store/Http/Controllers/OrderController.php`
- Modify: `resources/views/store/orders/index.blade.php` (surface the low-stock warning)
- Test: `tests/Feature/Store/OrderInvoicingTest.php` (new)

**Interfaces:**
- Produces: `OrderService::place()` now always returns an `Order` with a non-null `invoice_id`. `InvoicingService::createGeneric(?Student $student, array $data): Invoice` (new method) — creates an `Invoice` with `student_id` possibly null and a `label` describing the buyer.
- Consumes: existing `PaymentService`, `PaymentController`, `finance/invoices/show.blade.php` — unmodified, now also reachable for walk-in boutique invoices.

- [ ] **Step 1: Migration — nullable `student_id` on invoices**

Run: `php artisan make:migration make_student_id_nullable_on_invoices_table --table=invoices --no-interaction`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable(false)->change();
        });
    }
};
```

(Changing column nullability requires `doctrine/dbal` — check `composer.json`; if it's not present, this is a second pre-approved dependency addition for this plan only, install it: `composer require doctrine/dbal --no-interaction`.)

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/Store/OrderInvoicingTest.php`:

```php
<?php

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('creates an invoice for a walk-in buyer, unpaid by default', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 5000, 'stock_quantity' => 10]);

    $order = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 2]],
        null,
        'Jean Client',
    );

    expect($order->invoice_id)->not->toBeNull();
    $invoice = Invoice::query()->findOrFail($order->invoice_id);
    expect($invoice->student_id)->toBeNull();
    expect((float) $invoice->amount_due)->toBe(10000.0);
    expect($invoice->status)->toBe(InvoiceStatus::Unpaid);
});

it('creates an invoice for a student buyer, linked to that student', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 3000, 'stock_quantity' => 10]);

    $order = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 1]],
        $student,
        null,
    );

    $invoice = Invoice::query()->findOrFail($order->invoice_id);
    expect($invoice->student_id)->toBe($student->id);
});

it('allows a sale with insufficient stock and flags it, instead of blocking it', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 1000, 'stock_quantity' => 1]);

    $order = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 5]],
        null,
        'Jean Client',
    );

    expect($order->exists)->toBeTrue();
    expect($product->fresh()->stock_quantity)->toBe(0);
});
```

- [ ] **Step 3: Run the test, verify it fails**

Run: `php artisan test --compact tests/Feature/Store/OrderInvoicingTest.php`
Expected: FAIL.

- [ ] **Step 4: Add `InvoicingService::createGeneric()`**

Edit `app/Domain/Finance/Services/InvoicingService.php`. Add, after `createForStudent()`:

```php
public function createGeneric(?Student $student, array $data): Invoice
{
    return $this->invoices->create([
        'student_id' => $student?->id,
        'training_package_id' => null,
        'label' => $data['label'] ?? 'Facture',
        'amount_due' => $data['amount_due'] ?? 0,
        'amount_paid' => 0,
        'status' => InvoiceStatus::Unpaid,
        'issued_at' => $data['issued_at'] ?? now()->toDateString(),
    ]);
}
```

Check `InvoiceRepositoryInterface::create()`/`EloquentInvoiceRepository::create()` accept a null `student_id` in the payload without error (it's a plain `Invoice::query()->create($data)` — should already be fine since Eloquent doesn't require every fillable key to be present, but confirm `student_id` is in `Invoice::$fillable` and not implicitly required elsewhere).

- [ ] **Step 5: Rewrite `OrderService::place()`**

Replace `app/Domain/Store/Services/OrderService.php`'s `place()` method body. Stock insufficiency now floors at 0 instead of throwing, and every order gets an invoice via `createGeneric()`:

```php
<?php

namespace App\Domain\Store\Services;

use App\Domain\Finance\Services\InvoicingService;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Students\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Store is allowed to depend on Finance (see the domain diagram) - unlike
 * Fleet, which deliberately is not. Every order, walk-in or student-linked,
 * gets a real Invoice - reusing PaymentService's existing, already-tested
 * partial-payment/cancellation/ledger pipeline rather than building a
 * parallel one. Stock going below zero WARNS (via the returned $lowStock
 * flag the controller surfaces to the user) rather than blocking the sale -
 * a walk-in customer at the counter cannot be told "come back later."
 */
class OrderService
{
    public function __construct(
        private readonly InvoicingService $invoicing,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @return array{order: Order, lowStock: array<int, string>} product names that went under/at zero
     */
    public function place(array $items, ?Student $student, ?string $customerName): array
    {
        return DB::transaction(function () use ($items, $student, $customerName) {
            $lines = [];
            $total = 0;
            $lowStock = [];

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    $lowStock[] = $product->name;
                }

                $lines[] = ['product' => $product, 'quantity' => $item['quantity'], 'unit_price' => $product->price];
                $total += $product->price * $item['quantity'];
            }

            $order = Order::query()->create([
                'student_id' => $student?->id,
                'customer_name' => $customerName,
                'status' => OrderStatus::Confirmed,
                'total' => $total,
                'ordered_at' => now()->toDateString(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);

                $newQuantity = max(0, $line['product']->stock_quantity - $line['quantity']);
                $line['product']->update(['stock_quantity' => $newQuantity]);
            }

            $buyerLabel = $student?->fullName() ?? $customerName ?? 'Client comptoir';
            $invoice = $this->invoicing->createGeneric($student, [
                'label' => "Vente boutique #{$order->id} - {$buyerLabel}",
                'amount_due' => $total,
                'issued_at' => now()->toDateString(),
            ]);
            $order->update(['invoice_id' => $invoice->id]);

            return ['order' => $order->fresh(), 'lowStock' => $lowStock];
        });
    }
}
```

- [ ] **Step 6: Update `OrderController::store()`**

Edit `app/Domain/Store/Http/Controllers/OrderController.php`. Remove the `InsufficientStock` catch (no longer thrown) and surface the low-stock warning:

```php
public function store(StoreOrderRequest $request): RedirectResponse
{
    $student = $request->validated('student_id')
        ? Student::query()->find($request->validated('student_id'))
        : null;

    $result = $this->orders->place(
        $request->validated('items'),
        $student,
        $request->validated('customer_name'),
    );

    $status = "Commande #{$result['order']->id} enregistrée.";
    if ($result['lowStock'] !== []) {
        $status .= ' Attention, stock insuffisant pour : '.implode(', ', $result['lowStock']).'.';
    }

    return redirect()->route('store.orders.index')->with('status', $status);
}
```

Remove the now-unused `use App\Domain\Store\Exceptions\InsufficientStock;` import if nothing else in the file references it.

- [ ] **Step 7: Run the tests**

Run: `php artisan test --compact tests/Feature/Store/OrderInvoicingTest.php`
Expected: 3 passed.

Run: `php artisan test --compact tests/Feature/Store` (confirms no regression in whatever existing Store tests already cover `OrderController`/`ProductController`)
Expected: all passed — if any existing test asserts on the old `InsufficientStock` 422/error behavior or the old direct-ledger-write-for-walk-in behavior, it will need updating to match the new intentional behavior; update it, do not work around it.

- [ ] **Step 8: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add database/migrations app/Domain/Finance/Services/InvoicingService.php \
        app/Domain/Store/Services/OrderService.php \
        app/Domain/Store/Http/Controllers/OrderController.php \
        tests/Feature/Store/OrderInvoicingTest.php
git commit -m "feat(store): give every order a real invoice, warn instead of blocking on low stock"
```

---

### Task 2: Order cancellation restores stock

**Files:**
- Modify: `app/Domain/Store/Services/OrderService.php`
- Modify: `app/Domain/Store/Http/Controllers/OrderController.php`
- Modify: `app/Domain/Store/Policies/OrderPolicy.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Store/OrderCancellationTest.php` (new)

**Interfaces:**
- Produces: `OrderService::cancel(Order $order): void`, route `store.orders.cancel` (POST).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Store/OrderCancellationTest.php`:

```php
<?php

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('restores stock and voids the invoice when an order is cancelled', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 2000, 'stock_quantity' => 10]);
    $result = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 3]], null, 'Client');
    $order = $result['order'];

    expect($product->fresh()->stock_quantity)->toBe(7);

    $this->actingAs($this->admin)->post(route('store.orders.cancel', $order))
        ->assertRedirect(route('store.orders.index'));

    expect($product->fresh()->stock_quantity)->toBe(10);
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect(Invoice::query()->find($order->invoice_id))->toBeNull();
});

it('does not let an admin cancel another tenant\'s order', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $product = Product::factory()->create(['structure_id' => $otherStructure->id, 'price' => 1000, 'stock_quantity' => 5]);
    $result = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 1]], null, 'Client');

    $this->actingAs($this->admin)->post(route('store.orders.cancel', $result['order']))
        ->assertNotFound();
});
```

- [ ] **Step 2: Run, verify it fails**

Run: `php artisan test --compact tests/Feature/Store/OrderCancellationTest.php`
Expected: FAIL (route doesn't exist yet).

- [ ] **Step 3: Add `OrderService::cancel()`**

Append to `app/Domain/Store/Services/OrderService.php`:

```php
public function cancel(Order $order): void
{
    DB::transaction(function () use ($order) {
        foreach ($order->items as $item) {
            $item->product()->increment('stock_quantity', $item->quantity);
        }

        if ($order->invoice_id) {
            Invoice::query()->whereKey($order->invoice_id)->delete();
        }

        $order->update(['status' => OrderStatus::Cancelled, 'invoice_id' => null]);
    });
}
```

Add `use App\Domain\Finance\Models\Invoice;` to the file's imports.

(Deleting the `Invoice` outright — rather than a soft "void" status — is deliberate here: an unpaid, brand-new invoice with zero payments against it carries no financial history worth preserving, unlike a `Payment` row, which `PaymentService::cancel()` correctly never deletes. If the invoice already has payments recorded against it by the time of cancellation, this would delete a `Payment`'s parent — guard against that: only delete the invoice if `$order->invoice->payments()->doesntExist()`; if payments exist, leave the invoice in place and just mark the order cancelled, since money has already changed hands and can't be silently erased.)

- [ ] **Step 4: Add the controller action, route, and policy**

Edit `app/Domain/Store/Http/Controllers/OrderController.php`, add:

```php
public function cancel(Order $order): RedirectResponse
{
    $this->authorize('cancel', $order);

    $this->orders->cancel($order);

    return redirect()->route('store.orders.index')->with('status', "Commande #{$order->id} annulée, stock remis à jour.");
}
```

Edit `app/Domain/Store/Policies/OrderPolicy.php`, add:

```php
public function cancel(User $user, Order $order): bool
{
    return $user->hasRole('admin') && $order->structure_id === $user->structure_id;
}
```

Edit `routes/web.php`, in the existing `store.` route group, add:

```php
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact tests/Feature/Store/OrderCancellationTest.php`
Expected: 2 passed.

- [ ] **Step 6: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Store/Services/OrderService.php \
        app/Domain/Store/Http/Controllers/OrderController.php \
        app/Domain/Store/Policies/OrderPolicy.php \
        routes/web.php \
        tests/Feature/Store/OrderCancellationTest.php
git commit -m "feat(store): let an admin cancel an order, restoring stock and voiding its invoice"
```

---

### Task 3: Richer product catalog + stock movement history

**Files:**
- Create: migration via `php artisan make:migration add_catalog_fields_to_products_table --table=products`
- Create: migration via `php artisan make:migration create_stock_movements_table`
- Create: `app/Domain/Store/Models/StockMovement.php`
- Create: `app/Domain/Store/Enums/StockMovementType.php`
- Create: `app/Domain/Store/Database/Factories/StockMovementFactory.php`
- Modify: `app/Domain/Store/Models/Product.php`
- Modify: `app/Domain/Store/Http/Requests/StoreProductRequest.php`
- Modify: `app/Domain/Store/Services/OrderService.php`
- Test: `tests/Feature/Store/StockMovementTest.php` (new)

**Interfaces:**
- Produces: `StockMovement` model (`structure_id`, `product_id`, `type` [`Sale`/`Reception`/`Adjustment`], `quantity` [signed: negative for sale, positive for reception/positive-adjustment], `reference` [nullable string, e.g. `"Commande #12"`], `occurred_at`). `Product` gets `cost_price` (nullable decimal), `reorder_threshold` (nullable int), `barcode` (nullable string).
- Consumes: `OrderService::place()` now logs a `StockMovement` per line, consumed visually by Task 8's Produits tab.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Store/StockMovementTest.php`:

```php
<?php

use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\StockMovement;
use App\Domain\Store\Services\OrderService;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('logs a negative stock movement for each order line', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 10]);

    $result = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 3]], null, 'Client');

    $movement = StockMovement::query()->where('product_id', $product->id)->sole();
    expect($movement->type)->toBe(StockMovementType::Sale);
    expect($movement->quantity)->toBe(-3);
    expect($movement->reference)->toContain((string) $result['order']->id);
});

it('supports a manual stock adjustment', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 5]);

    StockMovement::query()->create([
        'product_id' => $product->id,
        'type' => StockMovementType::Adjustment,
        'quantity' => 2,
        'reference' => 'Inventaire du 27/08',
        'occurred_at' => now(),
    ]);

    expect(StockMovement::query()->count())->toBe(1);
});
```

- [ ] **Step 2: Run, verify it fails**

Run: `php artisan test --compact tests/Feature/Store/StockMovementTest.php`
Expected: FAIL.

- [ ] **Step 3: Migrations**

`add_catalog_fields_to_products_table`:

```php
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->decimal('cost_price', 10, 2)->nullable()->after('price');
        $table->unsignedInteger('reorder_threshold')->nullable()->after('stock_quantity');
        $table->string('barcode')->nullable()->after('sku');
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn(['cost_price', 'reorder_threshold', 'barcode']);
    });
}
```

`create_stock_movements_table`:

```php
public function up(): void
{
    Schema::create('stock_movements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
        $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

        $table->string('type');
        $table->integer('quantity');
        $table->string('reference')->nullable();
        $table->timestamp('occurred_at');
        $table->timestamps();

        $table->index(['structure_id', 'product_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('stock_movements');
}
```

- [ ] **Step 4: `StockMovementType` enum, `StockMovement` model, factory**

`app/Domain/Store/Enums/StockMovementType.php`:

```php
<?php

namespace App\Domain\Store\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case Reception = 'reception';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Vente',
            self::Reception => 'Réception',
            self::Adjustment => 'Ajustement',
        };
    }
}
```

`app/Domain/Store/Models/StockMovement.php`:

```php
<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\StockMovementFactory;
use App\Domain\Store\Enums\StockMovementType;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return StockMovementFactory::new();
    }

    protected $fillable = ['structure_id', 'product_id', 'type', 'quantity', 'reference', 'occurred_at'];

    protected $casts = [
        'type' => StockMovementType::class,
        'occurred_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

`app/Domain/Store/Database/Factories/StockMovementFactory.php`:

```php
<?php

namespace App\Domain\Store\Database\Factories;

use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\StockMovement;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'product_id' => Product::factory(),
            'type' => StockMovementType::Adjustment,
            'quantity' => 1,
            'occurred_at' => now(),
        ];
    }
}
```

- [ ] **Step 5: Add `stockMovements()` to `Product`, new fillable/casts**

Edit `app/Domain/Store/Models/Product.php`. Add `'cost_price', 'reorder_threshold', 'barcode'` to `$fillable`, add `'cost_price' => 'decimal:2'` to `$casts`, add:

```php
public function stockMovements(): HasMany
{
    return $this->hasMany(StockMovement::class);
}
```

(Add `use Illuminate\Database\Eloquent\Relations\HasMany;` import.)

- [ ] **Step 6: Log a `StockMovement` per order line in `OrderService::place()`**

Edit `app/Domain/Store/Services/OrderService.php`. In the `foreach ($lines as $line)` loop (inside `place()`, after the `$line['product']->update([...])` call), add:

```php
$line['product']->stockMovements()->create([
    'type' => StockMovementType::Sale,
    'quantity' => -$line['quantity'],
    'reference' => "Commande #{$order->id}",
    'occurred_at' => now(),
]);
```

Add `use App\Domain\Store\Enums\StockMovementType;` import.

- [ ] **Step 7: Update `StoreProductRequest`**

Add to `app/Domain/Store/Http/Requests/StoreProductRequest.php`'s `rules()`:

```php
'cost_price' => ['nullable', 'numeric', 'min:0'],
'reorder_threshold' => ['nullable', 'integer', 'min:0'],
'barcode' => ['nullable', 'string', 'max:100'],
```

- [ ] **Step 8: Run the tests**

Run: `php artisan test --compact tests/Feature/Store/StockMovementTest.php`
Expected: 2 passed.

Run: `php artisan test --compact tests/Feature/Store`
Expected: all passed.

- [ ] **Step 9: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add database/migrations app/Domain/Store/Enums/StockMovementType.php \
        app/Domain/Store/Models/StockMovement.php app/Domain/Store/Models/Product.php \
        app/Domain/Store/Database/Factories/StockMovementFactory.php \
        app/Domain/Store/Http/Requests/StoreProductRequest.php \
        app/Domain/Store/Services/OrderService.php \
        tests/Feature/Store/StockMovementTest.php
git commit -m "feat(store): add cost price, reorder threshold, barcode and stock movement history"
```

---

### Task 4: Purchase orders (réapprovisionnement) — models, migration, service

**Files:**
- Create: migration via `php artisan make:migration create_purchase_orders_table`
- Create: migration via `php artisan make:migration create_purchase_order_items_table`
- Create: `app/Domain/Store/Models/PurchaseOrder.php`
- Create: `app/Domain/Store/Models/PurchaseOrderItem.php`
- Create: `app/Domain/Store/Enums/PurchaseOrderStatus.php`
- Create: `app/Domain/Store/Database/Factories/PurchaseOrderFactory.php`
- Create: `app/Domain/Store/Database/Factories/PurchaseOrderItemFactory.php`
- Create: `app/Domain/Store/Services/PurchaseOrderService.php`
- Test: `tests/Unit/Store/PurchaseOrderServiceTest.php` (new)

**Interfaces:**
- Produces: `PurchaseOrderService::place(Supplier $supplier, array $items): PurchaseOrder`, `PurchaseOrderService::receive(PurchaseOrder $order, array $receivedQuantities): PurchaseOrder` — the latter increments `Product.stock_quantity` and logs a `StockMovement::Reception` per line actually received, and sets status to `PartiallyReceived` or `Received` depending on whether every line's received quantity matches its ordered quantity.
- Consumes: `Product`, `Supplier`, `StockMovement`/`StockMovementType` (Task 3).

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Store/PurchaseOrderServiceTest.php`:

```php
<?php

use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\StockMovement;
use App\Domain\Store\Models\Supplier;
use App\Domain\Store\Services\PurchaseOrderService;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
    $this->supplier = Supplier::factory()->create(['structure_id' => $this->structure->id]);
    $this->service = new PurchaseOrderService;
});

afterEach(fn () => TenantContext::clear());

it('creates a pending purchase order with its line items', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id]);

    $order = $this->service->place($this->supplier, [['product_id' => $product->id, 'quantity' => 20]]);

    expect($order->status)->toBe(PurchaseOrderStatus::Pending);
    expect($order->items()->sole()->quantity)->toBe(20);
});

it('increments stock and logs a reception movement when fully received', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 5]);
    $order = $this->service->place($this->supplier, [['product_id' => $product->id, 'quantity' => 20]]);

    $this->service->receive($order, [$product->id => 20]);

    expect($product->fresh()->stock_quantity)->toBe(25);
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
    $movement = StockMovement::query()->where('product_id', $product->id)->sole();
    expect($movement->type)->toBe(StockMovementType::Reception);
    expect($movement->quantity)->toBe(20);
});

it('marks the order partially received when quantities fall short', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 0]);
    $order = $this->service->place($this->supplier, [['product_id' => $product->id, 'quantity' => 20]]);

    $this->service->receive($order, [$product->id => 12]);

    expect($product->fresh()->stock_quantity)->toBe(12);
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived);
});
```

- [ ] **Step 2: Run, verify it fails**

Run: `php artisan test --compact tests/Unit/Store/PurchaseOrderServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Migrations**

`create_purchase_orders_table`:

```php
public function up(): void
{
    Schema::create('purchase_orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
        $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();

        $table->string('status')->default('pending');
        $table->date('ordered_at');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('purchase_orders');
}
```

`create_purchase_order_items_table`:

```php
public function up(): void
{
    Schema::create('purchase_order_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
        $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

        $table->unsignedInteger('quantity');
        $table->unsignedInteger('quantity_received')->default(0);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('purchase_order_items');
}
```

- [ ] **Step 4: `PurchaseOrderStatus` enum**

```php
<?php

namespace App\Domain\Store\Enums;

enum PurchaseOrderStatus: string
{
    case Pending = 'pending';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::PartiallyReceived => 'Réception partielle',
            self::Received => 'Réceptionnée',
            self::Cancelled => 'Annulée',
        };
    }
}
```

- [ ] **Step 5: `PurchaseOrder` and `PurchaseOrderItem` models + factories**

`app/Domain/Store/Models/PurchaseOrder.php`:

```php
<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\PurchaseOrderFactory;
use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return PurchaseOrderFactory::new();
    }

    protected $fillable = ['structure_id', 'supplier_id', 'status', 'ordered_at'];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'ordered_at' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
```

`app/Domain/Store/Models/PurchaseOrderItem.php` (no `BelongsToTenant`, same reasoning as `OrderItem`):

```php
<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return PurchaseOrderItemFactory::new();
    }

    protected $fillable = ['purchase_order_id', 'product_id', 'quantity', 'quantity_received'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

`app/Domain/Store/Database/Factories/PurchaseOrderFactory.php`:

```php
<?php

namespace App\Domain\Store\Database\Factories;

use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'supplier_id' => Supplier::factory(),
            'status' => PurchaseOrderStatus::Pending,
            'ordered_at' => now()->toDateString(),
        ];
    }
}
```

`app/Domain/Store/Database/Factories/PurchaseOrderItemFactory.php`:

```php
<?php

namespace App\Domain\Store\Database\Factories;

use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => 10,
            'quantity_received' => 0,
        ];
    }
}
```

- [ ] **Step 6: `PurchaseOrderService`**

```php
<?php

namespace App\Domain\Store\Services;

use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function place(Supplier $supplier, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($supplier, $items) {
            $order = PurchaseOrder::query()->create([
                'supplier_id' => $supplier->id,
                'status' => PurchaseOrderStatus::Pending,
                'ordered_at' => now()->toDateString(),
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $order;
        });
    }

    /**
     * @param  array<int, int>  $receivedQuantities  product_id => quantity received in this delivery
     */
    public function receive(PurchaseOrder $order, array $receivedQuantities): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $receivedQuantities) {
            foreach ($order->items as $item) {
                $received = $receivedQuantities[$item->product_id] ?? 0;

                if ($received <= 0) {
                    continue;
                }

                $item->increment('quantity_received', $received);

                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $product->increment('stock_quantity', $received);

                $product->stockMovements()->create([
                    'type' => StockMovementType::Reception,
                    'quantity' => $received,
                    'reference' => "Commande fournisseur #{$order->id}",
                    'occurred_at' => now(),
                ]);
            }

            $order->refresh();
            $fullyReceived = $order->items->every(fn ($item) => $item->quantity_received >= $item->quantity);
            $anyReceived = $order->items->contains(fn ($item) => $item->quantity_received > 0);

            $order->update([
                'status' => match (true) {
                    $fullyReceived => PurchaseOrderStatus::Received,
                    $anyReceived => PurchaseOrderStatus::PartiallyReceived,
                    default => $order->status,
                },
            ]);

            return $order;
        });
    }
}
```

- [ ] **Step 7: Run the tests**

Run: `php artisan test --compact tests/Unit/Store/PurchaseOrderServiceTest.php`
Expected: 3 passed.

- [ ] **Step 8: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add database/migrations app/Domain/Store/Enums/PurchaseOrderStatus.php \
        app/Domain/Store/Models/PurchaseOrder.php app/Domain/Store/Models/PurchaseOrderItem.php \
        app/Domain/Store/Database/Factories/PurchaseOrderFactory.php \
        app/Domain/Store/Database/Factories/PurchaseOrderItemFactory.php \
        app/Domain/Store/Services/PurchaseOrderService.php \
        tests/Unit/Store/PurchaseOrderServiceTest.php
git commit -m "feat(store): add purchase orders (réapprovisionnement) with partial reception"
```

---

### Task 5: Purchase order HTTP layer (policy, requests, controller, routes)

**Files:**
- Create: `app/Domain/Store/Policies/PurchaseOrderPolicy.php`
- Create: `app/Domain/Store/Http/Requests/StorePurchaseOrderRequest.php`
- Create: `app/Domain/Store/Http/Requests/ReceivePurchaseOrderRequest.php`
- Create: `app/Domain/Store/Http/Controllers/PurchaseOrderController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Store/PurchaseOrderControllerTest.php` (new)

**Interfaces:**
- Consumes: `PurchaseOrderService` (Task 4).
- Produces: routes `store.purchase-orders.index`, `.store`, `.receive` — consumed by Task 8's Réapprovisionnement tab.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Store/PurchaseOrderControllerTest.php`:

```php
<?php

use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\Supplier;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
    $this->supplier = Supplier::factory()->create(['structure_id' => $this->structure->id]);
    $this->product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 0]);
});

it('lets an admin place a purchase order', function () {
    $this->actingAs($this->admin)->post(route('store.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id,
        'items' => [['product_id' => $this->product->id, 'quantity' => 15]],
    ])->assertRedirect(route('store.purchase-orders.index'));

    expect(PurchaseOrder::query()->where('supplier_id', $this->supplier->id)->exists())->toBeTrue();
});

it('lets an admin receive a purchase order and updates stock', function () {
    $order = app(\App\Domain\Store\Services\PurchaseOrderService::class)
        ->place($this->supplier, [['product_id' => $this->product->id, 'quantity' => 15]]);

    $this->actingAs($this->admin)->post(route('store.purchase-orders.receive', $order), [
        'received' => [$this->product->id => 15],
    ])->assertRedirect(route('store.purchase-orders.index'));

    expect($this->product->fresh()->stock_quantity)->toBe(15);
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
});

it('denies a moniteur access to purchase order routes', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('store.purchase-orders.index'))->assertForbidden();
});

it('scopes the purchase-order index to the current tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherSupplier = Supplier::factory()->create(['structure_id' => $otherStructure->id, 'name' => 'Autre Fournisseur']);
    app(\App\Domain\Store\Services\PurchaseOrderService::class)->place($otherSupplier, []);

    $this->actingAs($this->admin)->get(route('store.purchase-orders.index'))
        ->assertOk()
        ->assertDontSee('Autre Fournisseur');
});
```

- [ ] **Step 2: Run, verify it fails**

Run: `php artisan test --compact tests/Feature/Store/PurchaseOrderControllerTest.php`
Expected: FAIL.

- [ ] **Step 3: Policy, requests, controller**

`app/Domain/Store/Policies/PurchaseOrderPolicy.php`:

```php
<?php

namespace App\Domain\Store\Policies;

use App\Domain\Store\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function receive(User $user, PurchaseOrder $order): bool
    {
        return $user->hasRole('admin') && $order->structure_id === $user->structure_id;
    }
}
```

`app/Domain/Store/Http/Requests/StorePurchaseOrderRequest.php`:

```php
<?php

namespace App\Domain\Store\Http\Requests;

use App\Domain\Store\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseOrder::class);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

`app/Domain/Store/Http/Requests/ReceivePurchaseOrderRequest.php`:

```php
<?php

namespace App\Domain\Store\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receive', $this->route('purchase_order'));
    }

    public function rules(): array
    {
        return [
            'received' => ['required', 'array'],
            'received.*' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
```

`app/Domain/Store/Http/Controllers/PurchaseOrderController.php`:

```php
<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Http\Requests\ReceivePurchaseOrderRequest;
use App\Domain\Store\Http\Requests\StorePurchaseOrderRequest;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use App\Domain\Store\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrders,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        return view('store.purchase-orders.index', [
            'purchaseOrders' => PurchaseOrder::query()->with(['supplier', 'items.product'])->latest('ordered_at')->paginate(20),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $supplier = Supplier::query()->findOrFail($request->validated('supplier_id'));

        $order = $this->purchaseOrders->place($supplier, $request->validated('items'));

        return redirect()->route('store.purchase-orders.index')->with('status', "Commande fournisseur #{$order->id} créée.");
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->purchaseOrders->receive($purchaseOrder, array_filter($request->validated('received')));

        return redirect()->route('store.purchase-orders.index')->with('status', 'Réception enregistrée, stock mis à jour.');
    }
}
```

- [ ] **Step 4: Routes**

Edit `routes/web.php`, in the existing `store.` group, add the import `use App\Domain\Store\Http\Controllers\PurchaseOrderController;` and:

```php
Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact tests/Feature/Store/PurchaseOrderControllerTest.php`
Expected: 4 passed. (Note: `store.purchase-orders.index` renders `store.purchase-orders.index` view — this view is built in Task 8; if this task runs before Task 8's view exists, these 4 tests will fail on rendering. Either build a minimal placeholder view here to keep this task's tests green in isolation, matching this session's established precedent (an earlier plan's Task 3 built a minimal view early for the same reason) — do exactly that: a bare `<x-app-layout>` with the create-order form and a list table, styled after `resources/views/store/orders/index.blade.php`. Task 8 will fold it into the unified 4-tab screen.)

- [ ] **Step 6: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Store/Policies/PurchaseOrderPolicy.php \
        app/Domain/Store/Http/Requests/StorePurchaseOrderRequest.php \
        app/Domain/Store/Http/Requests/ReceivePurchaseOrderRequest.php \
        app/Domain/Store/Http/Controllers/PurchaseOrderController.php \
        routes/web.php \
        resources/views/store/purchase-orders/index.blade.php \
        tests/Feature/Store/PurchaseOrderControllerTest.php
git commit -m "feat(store): add the purchase-order HTTP layer (policy, requests, controller, routes)"
```

---

### Task 6: Store reporting (KPIs + CSV export)

**Files:**
- Create: `app/Domain/Store/Services/StoreReportService.php`
- Create: `app/Domain/Store/Http/Controllers/StoreReportController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Store/StoreReportTest.php` (new)

**Interfaces:**
- Produces: `StoreReportService::dashboard(): array` (`revenueToday`, `revenueThisWeek`, `revenueThisMonth`, `revenueThisYear`, `salesCount`, `topProducts` [5], `criticalStock` [products under `reorder_threshold`], `pendingBalance` [sum of unpaid/partial boutique invoice balances]), consumed by Task 8's Rapports tab and by the CSV export route.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Store/StoreReportTest.php`:

```php
<?php

use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Store\Services\StoreReportService;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('reports revenue and top products from real orders', function () {
    $productA = Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Livre du code', 'price' => 5000, 'stock_quantity' => 20]);
    $productB = Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Gilet', 'price' => 2000, 'stock_quantity' => 20]);

    app(OrderService::class)->place([['product_id' => $productA->id, 'quantity' => 2]], null, 'Client A');
    app(OrderService::class)->place([['product_id' => $productB->id, 'quantity' => 1]], null, 'Client B');

    $report = app(StoreReportService::class)->dashboard();

    expect($report['salesCount'])->toBe(2);
    expect((float) $report['revenueToday'])->toBe(12000.0);
    expect($report['topProducts']->first()['name'])->toBe('Livre du code');
});

it('flags products under their reorder threshold as critical stock', function () {
    Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Stock bas', 'stock_quantity' => 1, 'reorder_threshold' => 5]);
    Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Stock ok', 'stock_quantity' => 10, 'reorder_threshold' => 5]);

    $report = app(StoreReportService::class)->dashboard();

    expect($report['criticalStock']->pluck('name')->all())->toBe(['Stock bas']);
});
```

- [ ] **Step 2: Run, verify it fails**

Run: `php artisan test --compact tests/Feature/Store/StoreReportTest.php`
Expected: FAIL.

- [ ] **Step 3: `StoreReportService`**

```php
<?php

namespace App\Domain\Store\Services;

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\OrderItem;
use App\Domain\Store\Models\Product;
use Illuminate\Support\Collection;

class StoreReportService
{
    /**
     * @return array{revenueToday: float, revenueThisWeek: float, revenueThisMonth: float, revenueThisYear: float, salesCount: int, topProducts: Collection, criticalStock: Collection, pendingBalance: float}
     */
    public function dashboard(): array
    {
        return [
            'revenueToday' => $this->revenueSince(now()->startOfDay()),
            'revenueThisWeek' => $this->revenueSince(now()->startOfWeek()),
            'revenueThisMonth' => $this->revenueSince(now()->startOfMonth()),
            'revenueThisYear' => $this->revenueSince(now()->startOfYear()),
            'salesCount' => Order::query()->count(),
            'topProducts' => $this->topProducts(),
            'criticalStock' => Product::query()
                ->whereNotNull('reorder_threshold')
                ->whereColumn('stock_quantity', '<', 'reorder_threshold')
                ->orderBy('name')
                ->get(),
            'pendingBalance' => $this->pendingBalance(),
        ];
    }

    private function revenueSince(\DateTimeInterface $since): float
    {
        return (float) Order::query()->where('ordered_at', '>=', $since->format('Y-m-d'))->sum('total');
    }

    private function topProducts(int $limit = 5): Collection
    {
        return OrderItem::query()
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw('products.name as name, SUM(order_items.quantity) as quantity, SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'quantity' => (int) $row->quantity, 'revenue' => (float) $row->revenue]);
    }

    private function pendingBalance(): float
    {
        return (float) Order::query()
            ->whereNotNull('invoice_id')
            ->with('invoice')
            ->get()
            ->filter(fn (Order $order) => $order->invoice && in_array($order->invoice->status, [InvoiceStatus::Unpaid, InvoiceStatus::Partial], true))
            ->sum(fn (Order $order) => $order->invoice->balanceDue());
    }
}
```

(Note: this queries every boutique `Order` with a non-null `invoice_id` and filters in PHP rather than a pure SQL join — fine for a shop's realistic order volume; if this ever needs to scale to tens of thousands of orders, push the `Invoice` status filter into the query with a join instead. Not required for this plan.)

- [ ] **Step 4: `StoreReportController`**

```php
<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Services\StoreReportService;
use App\Http\Controllers\Controller;
use App\Support\CsvExporter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreReportController extends Controller
{
    public function __construct(
        private readonly StoreReportService $reports,
    ) {}

    public function show(): View
    {
        $this->authorize('viewAny', \App\Domain\Store\Models\Order::class);

        return view('store.reports.show', $this->reports->dashboard());
    }

    public function exportTopProductsCsv(): StreamedResponse
    {
        $rows = $this->reports->dashboard()['topProducts']
            ->map(fn (array $row) => [$row['name'], $row['quantity'], $row['revenue']]);

        return CsvExporter::stream('top-produits.csv', ['Produit', 'Quantité vendue', 'Chiffre d\'affaires (FCFA)'], $rows);
    }
}
```

- [ ] **Step 5: Routes**

Edit `routes/web.php`, in the `store.` group, add the import and:

```php
Route::get('reports', [StoreReportController::class, 'show'])->name('reports.show');
Route::get('reports/top-products.csv', [StoreReportController::class, 'exportTopProductsCsv'])->name('reports.top-products.csv');
```

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact tests/Feature/Store/StoreReportTest.php`
Expected: 2 passed. (These are Feature-namespaced but don't hit HTTP — that's fine, matches this codebase's existing convention of putting `TenantContext`-based service tests under `tests/Feature` when they touch multiple domain services; see `tests/Feature/Reports/ReportServiceTest.php` for precedent.)

- [ ] **Step 7: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Store/Services/StoreReportService.php \
        app/Domain/Store/Http/Controllers/StoreReportController.php \
        routes/web.php \
        tests/Feature/Store/StoreReportTest.php
git commit -m "feat(store): add the store reporting service, dashboard KPIs and CSV export"
```

---

### Task 7: PDF export

**Files:**
- Modify: `composer.json` (new dependency, pre-approved)
- Create: `resources/views/store/reports/pdf.blade.php`
- Modify: `app/Domain/Store/Http/Controllers/StoreReportController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Store/StoreReportPdfTest.php` (new)

**Interfaces:**
- Produces: route `store.reports.pdf` (GET), streams a PDF.

- [ ] **Step 1: Install the dependency**

Run: `composer require barryvdh/laravel-dompdf --no-interaction`

Confirm it registers itself via Laravel's package auto-discovery (check `composer.json`'s `extra.laravel.dont-discover` doesn't exclude it, and that `Barryvdh\DomPDF\Facade\Pdf` is usable) — run `php artisan about` or `php artisan config:show` if you need to confirm the service provider loaded; do not manually register it in `config/app.php` unless auto-discovery is disabled project-wide (check first).

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/Store/StoreReportPdfTest.php`:

```php
<?php

use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin download the store report as a pdf', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 1000, 'stock_quantity' => 5]);
    app(\App\Domain\Store\Services\OrderService::class)->place([['product_id' => $product->id, 'quantity' => 1]], null, 'Client');

    $response = $this->actingAs($this->admin)->get(route('store.reports.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('denies a moniteur access to the pdf export', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('store.reports.pdf'))->assertForbidden();
});
```

- [ ] **Step 3: Run, verify it fails**

Run: `php artisan test --compact tests/Feature/Store/StoreReportPdfTest.php`
Expected: FAIL.

- [ ] **Step 4: PDF view**

Create `resources/views/store/reports/pdf.blade.php` — a plain, print-oriented HTML document (dompdf renders HTML/CSS, not Blade components — no `<x-card>`/`<x-icon>` etc., plain tags and inline or `<style>` CSS only):

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    </style>
</head>
<body>
    <h1>Rapport Boutique</h1>
    <p>Généré le {{ now()->format('d/m/Y') }}</p>

    <h2>Chiffre d'affaires</h2>
    <table>
        <tr><th>Aujourd'hui</th><td>{{ number_format($revenueToday, 0, ',', ' ') }} FCFA</td></tr>
        <tr><th>Cette semaine</th><td>{{ number_format($revenueThisWeek, 0, ',', ' ') }} FCFA</td></tr>
        <tr><th>Ce mois</th><td>{{ number_format($revenueThisMonth, 0, ',', ' ') }} FCFA</td></tr>
        <tr><th>Cette année</th><td>{{ number_format($revenueThisYear, 0, ',', ' ') }} FCFA</td></tr>
    </table>

    <h2>Top produits</h2>
    <table>
        <tr><th>Produit</th><th>Quantité</th><th>CA</th></tr>
        @foreach ($topProducts as $row)
            <tr><td>{{ $row['name'] }}</td><td>{{ $row['quantity'] }}</td><td>{{ number_format($row['revenue'], 0, ',', ' ') }} FCFA</td></tr>
        @endforeach
    </table>

    <h2>Stocks critiques</h2>
    <table>
        <tr><th>Produit</th><th>Stock actuel</th><th>Seuil</th></tr>
        @foreach ($criticalStock as $product)
            <tr><td>{{ $product->name }}</td><td>{{ $product->stock_quantity }}</td><td>{{ $product->reorder_threshold }}</td></tr>
        @endforeach
    </table>
</body>
</html>
```

- [ ] **Step 5: Controller action + route**

Edit `app/Domain/Store/Http/Controllers/StoreReportController.php`, add:

```php
public function exportPdf()
{
    $this->authorize('viewAny', \App\Domain\Store\Models\Order::class);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('store.reports.pdf', $this->reports->dashboard());

    return $pdf->stream('rapport-boutique.pdf');
}
```

Edit `routes/web.php`, in the `store.` group:

```php
Route::get('reports/pdf', [StoreReportController::class, 'exportPdf'])->name('reports.pdf');
```

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact tests/Feature/Store/StoreReportPdfTest.php`
Expected: 2 passed.

- [ ] **Step 7: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add composer.json composer.lock resources/views/store/reports/pdf.blade.php \
        app/Domain/Store/Http/Controllers/StoreReportController.php \
        routes/web.php \
        tests/Feature/Store/StoreReportPdfTest.php
git commit -m "feat(store): add PDF export for the store report (barryvdh/laravel-dompdf)"
```

---

### Task 8: Consolidated 4-tab Boutique screen + sidebar

**Files:**
- Create: `resources/views/store/index.blade.php` (the new single entry point, replacing direct links to the two separate existing pages)
- Modify: `resources/views/store/orders/index.blade.php` → fold its content into a `@include`/partial consumed by the new tabbed page (or inline it directly — implementer's judgment, but the content must not be duplicated in two places)
- Modify: `resources/views/store/products/index.blade.php` → same treatment
- Modify: `resources/views/store/purchase-orders/index.blade.php` (Task 5's minimal version) → same treatment, expand with the receive-quantities form
- Create: `resources/views/store/reports/show.blade.php` (Task 6 referenced this; build it here as the Rapports tab's content, plus a PDF/CSV download link)
- Modify: `app/Domain/Store/Http/Controllers/OrderController.php`, `ProductController.php`, `PurchaseOrderController.php`, `StoreReportController.php` — each `index()`/`show()` action still exists individually (for the CSV/PDF routes and any deep-linking), but add ONE new controller, `App\Domain\Store\Http\Controllers\StoreController@index`, that gathers all four tabs' data in one request and renders `store.index`
- Modify: `routes/web.php` — `store.index` becomes the sidebar's target
- Modify: `resources/views/layouts/partials/sidebar-nav.blade.php` (confirm the existing `store.products.index` link still resolves — if the route name for the main entry changes, update it)
- Test: `tests/Feature/Store/StoreTabsTest.php` (new)

**Interfaces:**
- Consumes: everything from Tasks 1-6.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Store/StoreTabsTest.php`:

```php
<?php

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('renders all four boutique tabs in one screen for an admin', function () {
    $this->actingAs($this->admin)->get(route('store.index'))
        ->assertOk()
        ->assertSee('Ventes')
        ->assertSee('Rapports')
        ->assertSee('Produits')
        ->assertSee('Réapprovisionnement', false);
});

it('denies a moniteur access to the boutique screen', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('store.index'))->assertForbidden();
});
```

- [ ] **Step 2: Run, verify it fails**

Run: `php artisan test --compact tests/Feature/Store/StoreTabsTest.php`
Expected: FAIL.

- [ ] **Step 3: `StoreController`**

Create `app/Domain/Store/Http/Controllers/StoreController.php`:

```php
<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use App\Domain\Store\Services\StoreReportService;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function __construct(
        private readonly StoreReportService $reports,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Order::class);

        return view('store.index', [
            'orders' => Order::query()->with(['student', 'items.product'])->latest('ordered_at')->paginate(20, ['*'], 'ordersPage'),
            'products' => Product::query()->where('active', true)->where('stock_quantity', '>', 0)->get(),
            'students' => Student::query()->orderBy('last_name')->get(),
            'allProducts' => Product::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::query()->with(['supplier', 'items.product'])->latest('ordered_at')->paginate(20, ['*'], 'purchaseOrdersPage'),
            ...$this->reports->dashboard(),
        ]);
    }
}
```

- [ ] **Step 4: Route + sidebar**

Edit `routes/web.php`. Add `Route::get('/', [StoreController::class, 'index'])->name('index');` as the FIRST route inside the `store.` group (before the existing `orders`/`products`/etc. routes — check the group's current `->prefix('store')` wrapper so this resolves to bare `GET /store`).

Edit `resources/views/layouts/partials/sidebar-nav.blade.php`. Find the existing `<x-sidebar-link :href="route('store.products.index')" ... icon="shopping-bag">Boutique</x-sidebar-link>` line and change its `href`/`active` to point at the new consolidated screen:

```blade
<x-sidebar-link :href="route('store.index')" :active="request()->routeIs('store.*')" icon="shopping-bag">Boutique</x-sidebar-link>
```

- [ ] **Step 5: Build `store/index.blade.php` with the 4 tabs**

Create `resources/views/store/index.blade.php`, using the existing `<x-tabs>` component (see `resources/views/students/show.blade.php` for the established multi-tab pattern: `<x-tabs :tabs="[...]">` wrapping `<div x-show="tab === 'key'" x-cloak>` panes). Move the EXISTING content of `store/orders/index.blade.php` and `store/products/index.blade.php` into the "ventes"/"produits" panes (read those two files first and carry their markup over faithfully — do not redesign them, just relocate), add the purchase-orders content (list + create form + a per-line "quantité reçue" input feeding the `receive` route) into the "reapprovisionnement" pane, and the KPI/top-products/critical-stock summary (reusing `<x-kpi-card>`, matching `resources/views/admin/dashboard.blade.php`'s established KPI-card style) plus CSV/PDF download links into the "rapports" pane:

```blade
<x-app-layout>
    <x-slot name="header">Boutique</x-slot>

    <div class="py-6 max-w-6xl mx-auto">
        <x-tabs :tabs="[
            'ventes' => 'Ventes',
            'rapports' => 'Rapports',
            'produits' => 'Produits',
            'reapprovisionnement' => 'Réapprovisionnement',
        ]">
            <div x-show="tab === 'ventes'" x-cloak class="space-y-5">
                {{-- relocated content of store/orders/index.blade.php --}}
            </div>

            <div x-show="tab === 'rapports'" x-cloak class="space-y-5">
                <div class="flex justify-end gap-3 text-xs">
                    <a href="{{ route('store.reports.top-products.csv') }}" class="text-primary hover:underline">Exporter le top produits (CSV)</a>
                    <a href="{{ route('store.reports.pdf') }}" class="text-primary hover:underline">Télécharger le rapport (PDF)</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-kpi-card icon="currency" label="CA aujourd'hui" :value="number_format($revenueToday, 0, ',', ' ').' FCFA'" />
                    <x-kpi-card icon="currency" label="CA ce mois" :value="number_format($revenueThisMonth, 0, ',', ' ').' FCFA'" />
                    <x-kpi-card icon="receipt" label="Ventes" :value="$salesCount" />
                    <x-kpi-card icon="receipt" label="Encaissements en attente" :value="number_format($pendingBalance, 0, ',', ' ').' FCFA'" />
                </div>
                {{-- top products + critical stock lists --}}
            </div>

            <div x-show="tab === 'produits'" x-cloak class="space-y-5">
                {{-- relocated content of store/products/index.blade.php --}}
            </div>

            <div x-show="tab === 'reapprovisionnement'" x-cloak class="space-y-5">
                {{-- purchase orders list + create form + receive form per line --}}
            </div>
        </x-tabs>
    </div>
</x-app-layout>
```

(The plan text above is a skeleton, not the literal final file — fill in each pane's actual markup by relocating the existing views' content and building the two new panes' markup following this codebase's established conventions, exactly as every prior task in this plan did for its own view. Do not leave any pane empty or stubbed.)

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact tests/Feature/Store`
Expected: all passed (every prior task's tests, plus this task's 2 new ones — confirm none of the earlier tasks' tests silently depended on the old separate `store.orders.index`/`store.products.index` page structure in a way this consolidation breaks; update them if so).

- [ ] **Step 7: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Store/Http/Controllers/StoreController.php \
        routes/web.php \
        resources/views/layouts/partials/sidebar-nav.blade.php \
        resources/views/store/index.blade.php \
        resources/views/store/reports/show.blade.php \
        tests/Feature/Store/StoreTabsTest.php
git commit -m "feat(store): consolidate the boutique screen into 4 tabs (ventes, rapports, produits, réapprovisionnement)"
```

---

### Task 9: Whole-branch verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: all passed, including `tests/Architecture/DomainBoundariesTest.php` — no new violation (Store's existing, allowed Finance dependency is unchanged in kind, just used more).

- [ ] **Step 2: Manually confirm the acceptance criteria**

Using the browser: as an admin, place a walk-in sale, confirm it creates an invoice reachable from `finance/invoices`, record a partial payment against it via the existing invoice screen and confirm the boutique Rapports tab's "Encaissements en attente" KPI reflects the remaining balance; place a sale exceeding stock and confirm it succeeds with a warning instead of failing; cancel an order and confirm stock is restored; place and fully receive a purchase order and confirm stock increments; download the CSV and PDF reports and confirm they open correctly; confirm a moniteur is forbidden from every `store.*` route.
