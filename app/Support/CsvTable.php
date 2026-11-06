<?php

namespace App\Support;

/**
 * Pembaca CSV sederhana tanpa library tambahan.
 * - Otomatis mengenali pemisah koma (,), titik-koma (;) atau tab.
 *   (Excel versi Indonesia menyimpan CSV dengan titik-koma.)
 * - Membuang BOM UTF-8 dan mengonversi Windows-1252 ke UTF-8.
 * - Melewati baris kosong.
 */
class CsvTable
{
    /**
     * @return array<int, array{line:int, cells:array<int,string>}>
     */
    public static function read(string $path): array
    {
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return [];
        }

        // File .xlsx adalah ZIP (diawali "PK"), bukan teks CSV
        if (str_starts_with($content, "PK\x03\x04")) {
            return self::readXlsx($path);
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $delimiter = self::detectDelimiter($content);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        $line = 0;
        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $line++;
            $cells = array_map(fn ($c) => trim((string) $c), $cells);
            if (implode('', $cells) === '') {
                continue; // baris kosong
            }
            $rows[] = ['line' => $line, 'cells' => $cells];
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Membaca sheet pertama berkas .xlsx tanpa library tambahan (butuh ekstensi zip & simplexml).
     * Catatan: sel angka panjang (mis. NIP 18 digit) harus berformat Teks di Excel,
     * kalau tidak Excel sendiri sudah membulatkannya.
     *
     * @return array<int, array{line:int, cells:array<int,string>}>
     */
    private static function readXlsx(string $path): array
    {
        if (!class_exists(\ZipArchive::class) || !function_exists('simplexml_load_string')) {
            throw new \RuntimeException('Ekstensi PHP zip/simplexml belum aktif. Aktifkan, atau simpan berkas sebagai CSV.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Berkas Excel tidak dapat dibuka.');
        }

        // Teks bersama (shared strings)
        $shared = [];
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false && ($xml = simplexml_load_string($ss)) !== false) {
            foreach ($xml->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text .= (string) $si->t;
                }
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
                $shared[] = $text;
            }
        }

        // Sheet pertama
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $name)) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false || ($sheet = simplexml_load_string($sheetXml)) === false) {
            throw new \RuntimeException('Isi berkas Excel tidak terbaca.');
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                // "C5" -> kolom ke-3 (indeks 2), supaya sel kosong di tengah tetap menjaga posisi
                preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                $col = 0;
                foreach (str_split($m[1] ?? 'A') as $ch) {
                    $col = $col * 26 + (ord($ch) - 64);
                }
                $col--;

                $type = (string) $c['t'];
                if ($type === 's') {
                    $val = $shared[(int) $c->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) $c->is->t;
                } else {
                    $val = (string) $c->v;
                }
                $cells[$col] = trim($val);
            }

            if (!$cells) {
                continue;
            }
            $filled = [];
            for ($i = 0; $i <= max(array_keys($cells)); $i++) {
                $filled[] = $cells[$i] ?? '';
            }
            if (implode('', $filled) === '') {
                continue;
            }
            $rows[] = ['line' => (int) $row['r'], 'cells' => $filled];
        }

        return $rows;
    }

    private static function detectDelimiter(string $content): string
    {
        $first = '';
        foreach (preg_split('/\r\n|\r|\n/', $content) as $l) {
            if (trim($l) !== '') {
                $first = $l;
                break;
            }
        }

        $counts = [
            ','  => substr_count($first, ','),
            ';'  => substr_count($first, ';'),
            "\t" => substr_count($first, "\t"),
        ];
        arsort($counts);

        return reset($counts) > 0 ? array_key_first($counts) : ',';
    }

    // Kunci pembanding: huruf besar tanpa spasi, jadi "X RPL 1" == "X RPL1" == "x rpl  1"
    public static function key(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', $value));
    }

    // Rapikan spasi ganda pada nama: "XI  AK1" -> "XI AK1"
    public static function clean(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
