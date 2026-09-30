<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFile;
use App\Services\OrderFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class OrderFileController extends Controller
{
    public function download(
        Request $request,
        int $id,
        string $file,
        OrderFileService $files,
    ): Response {
        $order = Order::query()->findOrFail($id);
        $resolvedFile = $files->resolve($order, $file, $request->user('admin'));

        if ($resolvedFile === null) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $resolvedFile['path'],
            $resolvedFile['filename'],
        );
    }

    public function downloadZip(
        Request $request,
        int $id,
        OrderFileService $files,
    ): Response {
        $order = Order::query()->findOrFail($id);
        $records = $files->recordsForOrder($order)
            ->filter(fn (OrderFile $file): bool => Storage::disk('public')->exists($file->path));

        if ($records->isEmpty()) {
            abort(404, 'This order has no downloadable files.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'order-files-');

        if ($zipPath === false) {
            abort(500, 'Unable to prepare the file archive.');
        }

        $zip = new ZipArchive;
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            @unlink($zipPath);
            abort(500, 'Unable to prepare the file archive.');
        }

        $usedNames = [];

        foreach ($records as $file) {
            $section = $file->status === OrderFileService::STATUS_CONFIRMED
                ? 'confirmed/v'.$file->version
                : 'awaiting-confirmation';
            $name = $this->uniqueArchiveName(
                $section.'/'.$file->original_name,
                $usedNames,
            );
            $zip->addFile(Storage::disk('public')->path($file->path), $name);
        }

        $zip->close();
        $files->recordZipDownload($order, $request->user('admin'));

        return response()
            ->download($zipPath, 'order-'.$order->getKey().'-files.zip', [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, bool>  $usedNames
     */
    private function uniqueArchiveName(string $name, array &$usedNames): string
    {
        $directory = trim(dirname($name), '.\\/')
            ? trim(str_replace('\\', '/', dirname($name)), '/').'/'
            : '';
        $baseName = trim(str_replace(['\\', '/'], '_', basename($name)));
        $base = pathinfo($baseName, PATHINFO_FILENAME);
        $extension = pathinfo($baseName, PATHINFO_EXTENSION);
        $candidate = $directory.$baseName;
        $index = 2;

        while (isset($usedNames[$candidate])) {
            $candidate = $directory.$base.'-'.$index.($extension !== '' ? '.'.$extension : '');
            $index++;
        }

        $usedNames[$candidate] = true;

        return $candidate;
    }
}
