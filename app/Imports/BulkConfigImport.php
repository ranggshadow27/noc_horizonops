<?php

namespace App\Imports;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\FacadesLog;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BulkConfigImport implements ToCollection, WithStartRow
{
    protected $configType;
    protected $zipFilePath;

    public function __construct(string $configType, string $zipFilePath)
    {
        $this->configType = $configType;
        $this->zipFilePath = $zipFilePath;
    }

    public function startRow(): int
    {
        return 1; // Mulai dari baris kedua (setelah header)
    }

    public function collection(Collection $rows)
    {
        // Inisialisasi ZIP
        $zip = new ZipArchive();
        Log::info('Creating ZIP file at: ' . $this->zipFilePath);

        if ($zip->open($this->zipFilePath, ZipArchive::CREATE) !== true) {
            Log::error('Failed to create ZIP file: ' . $this->zipFilePath);
            throw new \Exception('Gagal membuat file ZIP.');
        }

        $successCount = 0;
        $skippedRows = [];

        // Validasi header
        $headers = $rows->first()->map('strtolower')->toArray();
        $requiredColumns = ['ip_modem', 'ip_router', 'ip_ap1', 'ip_ap2', 'nama_lokasi', 'timezone'];
        if ($this->configType === 'grandstream') {
            $requiredColumns[] = 'ip_backup';
        }

        $columnIndexes = [];
        foreach ($requiredColumns as $col) {
            $index = array_search($col, $headers, true);
            $columnIndexes[$col] = $index !== false ? $index : null;
        }

        if ($columnIndexes['ip_modem'] === null) {
            Log::error('Missing required column: ip_modem');
            throw new \Exception('Kolom ip_modem wajib ada di file Excel.');
        }

        // Proses baris data (skip header)
        $rows->shift(); // Hapus header dari koleksi
        foreach ($rows as $rowIndex => $row) {
            Log::info('Processing row: ' . json_encode($row));

            // Cek apakah baris kosong
            if (empty(trim($row[$columnIndexes['ip_modem']] ?? ''))) {
                Log::info('Skipping empty row ' . ($rowIndex + 2));
                $skippedRows[] = $rowIndex + 2;
                continue;
            }

            $ipModem = $row[$columnIndexes['ip_modem']] ?? null;
            if (empty($ipModem) || !filter_var($ipModem, FILTER_VALIDATE_IP)) {
                Log::warning("Skipping row " . ($rowIndex + 2) . ": Invalid or empty ip_modem ($ipModem)");
                $skippedRows[] = $rowIndex + 2;
                continue;
            }

            $ipParts = explode('.', $ipModem);
            if (count($ipParts) !== 4) {
                Log::warning("Skipping row " . ($rowIndex + 2) . ": Invalid ip_modem format ($ipModem)");
                $skippedRows[] = $rowIndex + 2;
                continue;
            }

            // Ambil data dari Excel atau set default
            $ipRouter = $row[$columnIndexes['ip_router'] ?? -1] ?? null;
            $ipAp1 = $row[$columnIndexes['ip_ap1'] ?? -1] ?? null;
            $ipAp2 = $row[$columnIndexes['ip_ap2'] ?? -1] ?? null;
            $namaLokasi = $row[$columnIndexes['nama_lokasi'] ?? -1] ?? 'Unknown_Location';
            $timezone = $row[$columnIndexes['timezone'] ?? -1] ?? 'Asia/Jakarta';
            $ipBackup = $this->configType === 'grandstream' ? ($row[$columnIndexes['ip_backup'] ?? -1] ?? null) : null;

            // Generate default IPs jika kosong
            $ipPartsBase = $ipParts;
            if (empty($ipRouter)) {
                $ipPartsBase[3] = (int)$ipPartsBase[3] + 1;
                $ipRouter = implode('.', $ipPartsBase);
            }
            if (empty($ipAp1)) {
                $ipPartsBase[3] = (int)$ipPartsBase[3] + 1;
                $ipAp1 = implode('.', $ipPartsBase);
            }
            if (empty($ipAp2)) {
                $ipPartsBase[3] = (int)$ipPartsBase[3] + 1;
                $ipAp2 = implode('.', $ipPartsBase);
            }
            if ($this->configType === 'grandstream' && empty($ipBackup)) {
                $ipPartsBase[3] = (int)$ipPartsBase[3] + 1;
                $ipBackup = implode('.', $ipPartsBase);
            }

            // Validasi IP hasil generate
            if (!filter_var($ipRouter, FILTER_VALIDATE_IP) ||
                !filter_var($ipAp1, FILTER_VALIDATE_IP) ||
                !filter_var($ipAp2, FILTER_VALIDATE_IP) ||
                ($this->configType === 'grandstream' && !filter_var($ipBackup, FILTER_VALIDATE_IP))) {
                Log::warning("Skipping row " . ($rowIndex + 2) . ": Invalid generated IP addresses");
                $skippedRows[] = $rowIndex + 2;
                continue;
            }

            $cleanFileName = Str::replace(['-', '.', '/', '(', ')', "'"], '', trim($namaLokasi));

            if ($this->configType === 'mikrotik') {
                $ipParts[3] = (int)$ipParts[3] - 1;
                if ($ipParts[3] < 0) {
                    Log::warning("Skipping row " . ($rowIndex + 2) . ": ip_network cannot be negative");
                    $skippedRows[] = $rowIndex + 2;
                    continue;
                }
                $ipNetwork = implode('.', $ipParts);

                $template = Storage::disk('public')->get('templates/template_mikrotik.txt');
                $replacements = [
                    '${IP_NETWORK}' => $ipNetwork,
                    '${IP_MODEM}' => $ipModem,
                    '${IP_MIKROTIK}' => $ipRouter,
                    '${IP_AP1}' => $ipAp1,
                    '${IP_AP2}' => $ipAp2,
                    '${NAMA_LOKASI}' => $namaLokasi,
                    '${TIMEZONE}' => $timezone,
                ];

                $rscContent = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $template
                );

                $fileName = $cleanFileName . '.rsc';
                Storage::put('temp/' . $fileName, $rscContent);
                $zip->addFile(storage_path('app/temp/' . $fileName), $fileName);
            } else {
                $template = Storage::disk('public')->get('templates/template_gs.txt');
                $replacements = [
                    '{$IP_MODEM}' => $ipModem,
                    'IP BACKUP' => $ipBackup,
                    '{$IP_ROUTER}' => $ipRouter,
                    '{$IP_AP1}' => $ipAp1,
                    '{$IP_AP2}' => $ipAp2,
                    '{$NAMA_LOKASI}' => $namaLokasi,
                    '{$TIMEZONE}' => $timezone,
                ];

                $configContent = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $template
                );

                $txtFileName = $cleanFileName . '_gscfg';
                $binFileName = $cleanFileName . '_gscfg.bin';
                Storage::put('temp/' . $txtFileName, $configContent);

                $txtFilePath = storage_path('app/temp/' . $txtFileName);
                $binFilePath = base_path('bin/' . $binFileName);
                $binFolderPath = base_path('bin');

                $command = sprintf('cd "%s" && ./gscfgtool -t GWN7003 -e "%s"', $binFolderPath, $txtFilePath);
                $output = shell_exec($command . ' 2>&1');

                if (!file_exists($binFilePath)) {
                    Log::warning("Skipping row " . ($rowIndex + 2) . ": Failed to generate Grandstream bin - $output");
                    $skippedRows[] = $rowIndex + 2;
                    Storage::delete('temp/' . $txtFileName);
                    continue;
                }

                $zip->addFile($binFilePath, $binFileName);
                Storage::delete('temp/' . $txtFileName);
            }

            $successCount++;
        }

        $zip->close();

        // Cleanup files kecuali file ZIP
        $files = Storage::files('temp');
        foreach ($files as $file) {
            if (basename($file) !== basename($this->zipFilePath)) {
                Storage::delete($file);
            }
        }
        if (file_exists(base_path('bin'))) {
            $binFiles = glob(base_path('bin/*_gscfg.bin'));
            foreach ($binFiles as $file) {
                unlink($file);
            }
        }

        if ($successCount === 0) {
            Log::error('No configurations generated successfully.');
            Storage::delete(basename($this->zipFilePath)); // Hapus ZIP jika gagal
            throw new \Exception('Tidak ada konfigurasi yang berhasil di-generate.');
        }

        $message = "Berhasil generate {$successCount} konfigurasi.";
        if (!empty($skippedRows)) {
            $message .= " Baris yang dilewati (karena ip_modem atau IP lain tidak valid/kosong): " . implode(', ', $skippedRows);
        }
        Log::info($message);

        // File ZIP tidak di-return di sini, karena download dilakukan di GenerateMikrotikConfig
    }
}
