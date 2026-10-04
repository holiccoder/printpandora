<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderFileController extends Controller
{
    public function upload(
        Request $request,
        int $id,
        OrderFileService $files,
    ): RedirectResponse {
        $order = $this->customerOrder($request, $id);

        abort_unless($files->canCustomerManage($order), 403);

        $validated = $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => [
                'required',
                'file',
                'max:76800',
                'mimes:jpg,jpeg,png,webp,pdf,svg,ai,eps,psd,tiff',
            ],
        ]);

        $files->upload($order, $validated['files'], 'customer', $request->user());

        return back()->with('success', 'Files uploaded successfully.');
    }

    public function destroy(
        Request $request,
        int $id,
        string $file,
        OrderFileService $files,
    ): RedirectResponse {
        $order = $this->customerOrder($request, $id);

        abort_unless($files->canCustomerManage($order), 403);

        $files->delete($order, $file, $request->user());

        return back()->with('success', 'File deleted.');
    }

    public function confirm(
        Request $request,
        int $id,
        OrderFileService $files,
    ): RedirectResponse {
        $order = $this->customerOrder($request, $id);
        $customer = $request->user();

        abort_unless($customer instanceof User, 401);
        $files->confirmForCustomer($order, $customer);

        return back()->with('success', 'The files have been confirmed.');
    }

    private function customerOrder(Request $request, int $id): Order
    {
        return Order::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->findOrFail($id);
    }
}
