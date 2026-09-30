<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\ProductDesignRequest;
use App\Models\User;
use App\Services\OrderFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderFileWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_confirmation_promotes_the_pending_version_and_keeps_history(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = Admin::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_PENDING_REVIEW);
        $files = app(OrderFileService::class);

        $uploaded = $files->upload(
            $order,
            [UploadedFile::fake()->create('customer.pdf', 10, 'application/pdf')],
            'customer',
            $user,
        )->firstOrFail();
        $files->submitForConfirmation($order, $admin);

        $order->refresh();
        $this->assertSame(Order::STATUS_PENDING_CONFIRMATION, $order->status);

        $files->resolve($order, $files->identifier($uploaded), $user);
        $files->confirmForCustomer($order, $user);
        $order->refresh();

        $this->assertSame(Order::STATUS_CONFIRMED, $order->status);
        $this->assertSame(1, OrderFile::query()->where('order_id', $order->id)->count());
        $this->assertDatabaseHas('order_files', [
            'order_id' => $order->id,
            'status' => OrderFileService::STATUS_CONFIRMED,
            'is_current' => true,
            'confirmed_by_user_id' => $user->id,
        ]);

        $order->update(['status' => Order::STATUS_PENDING_CONFIRMATION]);
        $uploaded = $files->upload(
            $order,
            [UploadedFile::fake()->create('admin-proof.pdf', 10, 'application/pdf')],
            'admin',
            $admin,
        )->firstOrFail();
        $files->resolve($order, $files->identifier($uploaded), $user);
        $files->confirmForCustomer($order, $user);

        $this->assertSame(
            2,
            OrderFile::query()->where('order_id', $order->id)->where('status', OrderFileService::STATUS_CONFIRMED)->count(),
        );
        $this->assertSame(
            1,
            OrderFile::query()->where('order_id', $order->id)->where('is_current', true)->count(),
        );
        $this->assertDatabaseHas('order_file_audits', [
            'order_id' => $order->id,
            'actor_type' => 'customer',
            'actor_id' => $user->id,
            'action' => 'customer_confirmed',
        ]);
    }

    public function test_customer_must_download_a_pending_file_before_confirmation(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = Admin::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_PENDING_REVIEW);
        $files = app(OrderFileService::class);
        $file = $files->upload(
            $order,
            [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')],
            'admin',
            $admin,
        )->firstOrFail();
        $files->submitForConfirmation($order, $admin);

        $this->assertFalse($files->canCustomerConfirm($order, $user));

        try {
            $files->confirmForCustomer($order, $user);
            $this->fail('The customer should not confirm files before downloading them.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('files', $exception->errors());
        }

        $this->assertSame(
            Order::STATUS_PENDING_CONFIRMATION,
            $order->fresh()->status,
        );

        $this->actingAs($user)
            ->get(route('dashboard.orders.file', [
                'id' => $order->id,
                'file' => $files->identifier($file),
            ]))
            ->assertOk()
            ->assertDownload('proof.pdf');

        $this->assertTrue($files->canCustomerConfirm($order->fresh(), $user));

        $files->confirmForCustomer($order->fresh(), $user);

        $this->assertSame(Order::STATUS_CONFIRMED, $order->fresh()->status);
    }

    public function test_customer_deleting_a_file_during_confirmation_reopens_review(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = Admin::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_PENDING_REVIEW);
        $files = app(OrderFileService::class);

        $uploaded = $files->upload(
            $order,
            [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')],
            'admin',
            $admin,
        )->firstOrFail();
        $files->submitForConfirmation($order, $admin);

        $response = $this->actingAs($user)->delete(
            route('dashboard.orders.files.destroy', [
                'id' => $order->id,
                'file' => $files->identifier($uploaded),
            ]),
        );

        $response->assertRedirect();
        $this->assertSame(Order::STATUS_PENDING_REVIEW, $order->fresh()->status);
        $this->assertDatabaseMissing('order_files', ['id' => $uploaded->id]);
        Storage::disk('public')->assertMissing($uploaded->path);
    }

    public function test_customer_cannot_modify_confirmed_order_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_CONFIRMED);

        $response = $this->actingAs($user)->post(
            route('dashboard.orders.files.upload', $order->id),
            ['files' => [UploadedFile::fake()->create('late.pdf', 10, 'application/pdf')]],
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('order_files', 0);
    }

    public function test_customer_cannot_delete_confirmed_file_history(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = Admin::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_CONFIRMED);
        $files = app(OrderFileService::class);
        $file = $files->upload(
            $order,
            [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')],
            'admin',
            $admin,
        )->firstOrFail();
        $file->update([
            'status' => OrderFileService::STATUS_CONFIRMED,
            'is_current' => true,
        ]);

        $order->update(['status' => Order::STATUS_PENDING_CONFIRMATION]);

        $response = $this->actingAs($user)->delete(
            route('dashboard.orders.files.destroy', [
                'id' => $order->id,
                'file' => $files->identifier($file),
            ]),
        );

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseHas('order_files', ['id' => $file->id]);
        Storage::disk('public')->assertExists($file->path);
    }

    public function test_customer_upload_after_reupload_request_returns_order_to_review(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_NEEDS_REUPLOAD);

        $response = $this->actingAs($user)->post(
            route('dashboard.orders.files.upload', $order->id),
            ['files' => [UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf')]],
        );

        $response->assertRedirect();
        $this->assertSame(Order::STATUS_PENDING_REVIEW, $order->fresh()->status);
        $this->assertDatabaseHas('order_files', [
            'order_id' => $order->id,
            'original_name' => 'replacement.pdf',
            'source' => 'customer',
        ]);
    }

    public function test_admin_file_payload_identifies_the_customer_or_admin_uploader(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = Admin::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_PENDING_REVIEW);
        $files = app(OrderFileService::class);

        $files->upload(
            $order,
            [UploadedFile::fake()->create('customer.pdf', 10, 'application/pdf')],
            'customer',
            $user,
        );
        $files->upload(
            $order,
            [UploadedFile::fake()->create('admin.pdf', 10, 'application/pdf')],
            'admin',
            $admin,
        );

        $awaiting = $files->forOrder($order, 'admin')['awaiting_confirmation'];
        $awaitingByFilename = collect($awaiting)->keyBy('filename');

        $this->assertSame(
            ['admin', $admin->id],
            [
                $awaitingByFilename['admin.pdf']['uploader_type'],
                $awaitingByFilename['admin.pdf']['uploader_id'],
            ],
        );
        $this->assertSame(
            ['customer', $user->id],
            [
                $awaitingByFilename['customer.pdf']['uploader_type'],
                $awaitingByFilename['customer.pdf']['uploader_id'],
            ],
        );

        $html = view('filament.pages.order-files-table', [
            'files' => [
                $awaitingByFilename['customer.pdf'],
                $awaitingByFilename['admin.pdf'],
            ],
            'showUploader' => true,
            'deleteAction' => null,
            'empty' => '暂无文件。',
        ])->render();

        $this->assertStringContainsString('客户'.$user->id, $html);
        $this->assertStringContainsString('管理员'.$admin->id, $html);
    }

    public function test_admin_file_modal_identifies_customer_uploader_for_legacy_customer_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_PENDING_CONFIRMATION);
        $path = 'product-designs/legacy/customer-design.pdf';

        Storage::disk('public')->put($path, 'customer design');
        ProductDesignRequest::create([
            'order_id' => $order->id,
            'desgin' => [
                'design_path' => $path,
            ],
        ]);

        $payload = app(OrderFileService::class)->forOrder($order, 'admin');
        $file = collect($payload['awaiting_confirmation'])
            ->firstWhere('filename', 'customer-design.pdf');

        $this->assertIsArray($file);
        $legacyRecord = OrderFile::query()
            ->where('order_id', $order->id)
            ->where('identifier', $file['id'])
            ->firstOrFail();
        $legacyRecord->update(['uploaded_by_user_id' => null]);
        app(OrderFileService::class)->forOrder($order, 'admin');

        $this->assertDatabaseHas('order_files', [
            'id' => $legacyRecord->id,
            'uploaded_by_user_id' => $user->id,
        ]);
        $this->assertSame('customer', $file['uploader_type']);
        $this->assertSame($user->id, $file['uploader_id']);

        $html = view('filament.pages.order-files-table', [
            'files' => [$file],
            'showUploader' => true,
            'deleteAction' => null,
            'empty' => '暂无文件。',
        ])->render();

        $this->assertStringContainsString('客户'.$user->id, $html);
        $this->assertStringNotContainsString('—', $html);
    }

    public function test_admin_can_download_every_order_file_as_a_zip(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $admin = Admin::factory()->create();
        $order = $this->makeOrder($user, Order::STATUS_PENDING_REVIEW);
        $files = app(OrderFileService::class);
        $files->upload(
            $order,
            [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')],
            'customer',
            $user,
        );

        $response = $this->actingAs($admin, 'admin')->get(
            route('admin.orders.files.zip', $order->id),
        );

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
        $this->assertDatabaseHas('order_file_audits', [
            'order_id' => $order->id,
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'action' => 'downloaded_zip',
        ]);
    }

    private function makeOrder(User $user, string $status): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'status' => $status,
            'payment_status' => 'paid',
            'total' => 20,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'shipping_carrier' => 'Standard',
            'shipping_fee' => 0,
        ]);
    }
}
