<?php

use App\Filament\Pages\DashboardHandover;
use App\Filament\Pages\PublicDashboard;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/public-dashboard', PublicDashboard::class)->name('public-dashboard');

Route::get('/export-zip/{id}', function ($id) {
    // Tentukan folder tempat file gambar
    $folder = "tmo-images/{$id}";
    $images = Storage::disk('public')->files($folder);

    // Cek apakah ada gambar dalam folder
    if (empty($images)) {
        return back()->withErrors('Tidak ada gambar untuk diekspor.');
    }

    // Nama file ZIP yang akan diunduh
    $zipFileName = "tmo_images_{$id}.zip";

    // Membuat StreamedResponse untuk mendownload file ZIP
    return response()->streamDownload(function () use ($images, $folder) {
        // Membuat instance ZipArchive
        $zip = new \ZipArchive;

        // Nama file sementara yang digunakan untuk menyimpan ZIP
        $tempFile = tempnam(sys_get_temp_dir(), 'zip_');

        // Membuka ZIP untuk menambahkan file
        if ($zip->open($tempFile, \ZipArchive::CREATE) !== TRUE) {
            exit("Tidak dapat membuka file ZIP");
        }

        // Menambahkan setiap gambar ke dalam ZIP
        foreach ($images as $image) {
            // Ambil konten file gambar
            $fileContent = Storage::disk('public')->get($image);
            $zip->addFromString(basename($image), $fileContent);
        }

        // Menyelesaikan pembuatan file ZIP
        $zip->close();

        // Membaca file ZIP dan mengirimkan sebagai response
        readfile($tempFile);

        // Hapus file sementara setelah digunakan
        unlink($tempFile);
    }, $zipFileName, [
        "Content-Type" => "application/zip",
        "Content-Disposition" => "attachment; filename={$zipFileName}",
    ]);
})->name('export.zip');

Route::get('/download-bulk-config/{filename}', function ($filename) {
    $path = storage_path('app/temp/' . $filename);
    if (!file_exists($path)) {
        abort(404, 'File not found');
    }
    return response()->download($path, $filename, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
})->name('download-bulk-config');

Route::get('/', function () {
    return redirect('/mahaga');
});
