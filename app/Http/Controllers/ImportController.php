<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\ImportPreview;
use App\Services\AuditLogger;
use App\Services\ExcelImporterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportController extends Controller
{
    public function index()
    {
        $imports = ImportBatch::query()->latest()->paginate(10);
        return view('import.index', compact('imports'));
    }

    public function preview(Request $request, ExcelImporterService $importer): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'extensions:xlsx,xls,csv'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.extensions' => 'File harus berformat XLSX, XLS, atau CSV.',
            'file.max' => 'Ukuran file maksimal 50 MB.',
        ]);

        $path = $validated['file']->store('imports/previews');
        $absolutePath = Storage::path($path);

        try {
            $extension = strtolower($validated['file']->getClientOriginalExtension());
            if ($extension === 'xlsx' && !class_exists('ZipArchive')) {
                throw new \RuntimeException('Ekstensi PHP ZIP belum aktif. Aktifkan extension=zip pada php.ini lalu restart PHP.');
            }

            IOFactory::identify($absolutePath);
            $summary = $importer->preview($absolutePath);

            $preview = ImportPreview::create([
                'user_id' => $request->user()->id,
                'filename' => $validated['file']->getClientOriginalName(),
                'path' => $path,
                'summary' => $summary,
                'status' => 'pending',
                'expires_at' => now()->addHours(2),
            ]);

            return redirect()->route('import.preview', $preview);
        } catch (Throwable $e) {
            Storage::delete($path);
            report($e);
            return back()->withErrors(['file' => 'File dapat dibaca tetapi preview gagal dibuat. Detail: ' . mb_substr($e->getMessage(), 0, 240)]);
        }
    }

    public function showPreview(Request $request, ImportPreview $preview)
    {
        abort_unless($preview->user_id === $request->user()->id, 403);
        if ($preview->status !== 'pending' || ($preview->expires_at && $preview->expires_at->isPast())) {
            return redirect()->route('import.index')->withErrors(['file' => 'Preview sudah kedaluwarsa. Silakan unggah ulang file.']);
        }

        return view('import.preview', compact('preview'));
    }

    public function confirm(Request $request, ImportPreview $preview, ExcelImporterService $importer, AuditLogger $audit): RedirectResponse
    {
        abort_unless($preview->user_id === $request->user()->id, 403);

        if ($preview->status !== 'pending' || ($preview->expires_at && $preview->expires_at->isPast())) {
            return redirect()->route('import.index')->withErrors(['file' => 'Preview sudah kedaluwarsa. Silakan unggah ulang file.']);
        }

        $summary = $preview->summary ?? [];
        if (!empty($summary['blocking_errors'])) {
            return redirect()->route('import.preview', $preview)
                ->withErrors(['file' => 'Import belum dapat dikonfirmasi karena preview menemukan masalah yang harus diperbaiki terlebih dahulu.']);
        }

        $absolutePath = Storage::path($preview->path);
        if (!is_file($absolutePath)) {
            $preview->update(['status' => 'expired']);
            return redirect()->route('import.index')->withErrors(['file' => 'File preview sudah tidak tersedia. Silakan unggah ulang.']);
        }

        try {
            $batch = $importer->import($absolutePath);
            $batch->update(['filename' => $preview->filename]);
            $preview->update(['status' => 'imported']);

            $audit->log('excel_imported', 'Import Excel berhasil: ' . $batch->filename, $batch, null, [
                'filename' => $batch->filename,
                'total_sheets' => $batch->total_sheets,
                'total_asns' => $batch->total_asns,
                'total_trainings' => $batch->total_trainings,
                'total_competencies' => $batch->total_competencies,
            ]);

            Storage::delete($preview->path);
            return redirect()->route('import.index')->with('success', "Import {$batch->filename} berhasil setelah preview dikonfirmasi.");
        } catch (Throwable $e) {
            report($e);
            $preview->update(['status' => 'failed']);
            $audit->log('excel_import_failed', 'Import Excel gagal: ' . $preview->filename, null, null, [
                'filename' => $preview->filename,
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            $message = 'Import dibatalkan karena terjadi kesalahan. Tidak ada perubahan yang seharusnya tersimpan dari transaksi import.';
            if (app()->environment('local')) $message .= ' Detail: ' . mb_substr($e->getMessage(), 0, 300);
            return redirect()->route('import.index')->withErrors(['file' => $message]);
        }
    }

    public function cancel(Request $request, ImportPreview $preview): RedirectResponse
    {
        abort_unless($preview->user_id === $request->user()->id, 403);
        if ($preview->path) Storage::delete($preview->path);
        $preview->update(['status' => 'cancelled']);
        return redirect()->route('import.index')->with('success', 'Preview dibatalkan. Database tidak diubah.');
    }

    /** Backward-compatible endpoint: upload sekarang selalu masuk tahap preview. */
    public function store(Request $request): RedirectResponse
    {
        return $this->preview($request, app(ExcelImporterService::class));
    }
}
