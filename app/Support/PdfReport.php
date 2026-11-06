<?php

namespace App\Support;

/**
 * Pembuat PDF sederhana tanpa library tambahan (tidak perlu composer require).
 * Cocok untuk laporan tabel: kop sekolah, judul, keterangan filter, tabel berpindah halaman
 * otomatis (judul kolom diulang), dan footer "Halaman X dari Y".
 *
 * Pemakaian:
 *   $pdf = new PdfReport(landscape: false, printedBy: 'Nama (username)');
 *   $pdf->title('Rekap Presensi');
 *   $pdf->meta([['Periode', '01 Nov 2026 s/d 06 Nov 2026']]);
 *   $pdf->table([['label' => 'No', 'fixed' => 28, 'align' => 'C'], ['label' => 'Nama', 'w' => 3]], $rows);
 *   return $pdf->download('rekap.pdf');
 *
 * Kolom: 'label', lebar tetap 'fixed' (poin) ATAU bobot 'w' (dibagi dari sisa lebar), 'align' L|C|R.
 * Font: Helvetica bawaan PDF (huruf Latin / Indonesia; karakter di luar Windows-1252 jadi "?").
 */
class PdfReport
{
    private float $pw;
    private float $ph;
    private float $ml = 40.0;
    private float $mr = 40.0;
    private float $mt = 40.0;
    private float $mb = 46.0;
    private float $y;

    /** @var string[] isi (content stream) tiap halaman yang sudah selesai */
    private array $pages = [];
    /** @var string[] perintah gambar halaman yang sedang dikerjakan */
    private array $cur = [];

    private ?string $logoData = null;
    private int $logoW = 0;
    private int $logoH = 0;

    private string $printedBy;
    private string $printedAt;

    public function __construct(bool $landscape = false, string $printedBy = '', ?string $logoPath = null)
    {
        $this->pw = $landscape ? 841.89 : 595.28;
        $this->ph = $landscape ? 595.28 : 841.89;
        $this->printedBy = $printedBy;
        $this->printedAt = now('Asia/Jakarta')->locale('id')->translatedFormat('d M Y H:i') . ' WIB';

        $logoPath = $logoPath ?? resource_path('pdf/logo.jpg');
        if (is_file($logoPath)) {
            $info = @getimagesize($logoPath);
            // hanya JPEG RGB (3 komponen) yang dipakai agar embed-nya sederhana & aman
            if ($info !== false && ($info[2] ?? 0) === IMAGETYPE_JPEG && ($info['channels'] ?? 3) === 3) {
                $data = file_get_contents($logoPath);
                if ($data !== false) {
                    $this->logoData = $data;
                    $this->logoW = (int) $info[0];
                    $this->logoH = (int) $info[1];
                }
            }
        }

        $this->startPage(true);
    }

    // ------------------------------------------------------------------ elemen laporan

    public function title(string $text): void
    {
        $this->ensure(24);
        $this->text($this->ml, $this->y + 12, $this->enc($text), 13, true);
        $this->y += 20;
    }

    /** @param array<int, array{0:string,1:mixed}> $pairs pasangan [label, nilai] */
    public function meta(array $pairs): void
    {
        foreach ($pairs as $pair) {
            $this->ensure(13);
            $this->text($this->ml, $this->y + 9, $this->enc((string) $pair[0]), 9, false, [0.42, 0.44, 0.47]);
            $this->text($this->ml + 78, $this->y + 9, $this->enc(': ' . $pair[1]), 9);
            $this->y += 12.5;
        }
        $this->y += 6;
    }

    public function paragraph(string $text, float $size = 9, bool $bold = false): void
    {
        $lines = $this->wrap($this->enc($text), $this->pw - $this->ml - $this->mr, $size, $bold);
        foreach ($lines as $line) {
            $this->ensure($size * 1.4);
            $this->text($this->ml, $this->y + $size, $line, $size, $bold);
            $this->y += $size * 1.4;
        }
        $this->y += 4;
    }

    public function gap(float $h): void
    {
        $this->y += $h;
    }

    /**
     * @param array<int, array<string, mixed>> $cols
     * @param array<int, array<int, mixed>>    $rows
     */
    public function table(array $cols, array $rows, float $size = 8.5): void
    {
        $tw = $this->pw - $this->ml - $this->mr;
        $fixed = 0.0;
        $weight = 0.0;
        foreach ($cols as $c) {
            if (isset($c['fixed'])) {
                $fixed += (float) $c['fixed'];
            } else {
                $weight += (float) ($c['w'] ?? 1);
            }
        }
        $widths = [];
        foreach ($cols as $c) {
            $widths[] = isset($c['fixed'])
                ? (float) $c['fixed']
                : ($weight > 0 ? ($tw - $fixed) * (float) ($c['w'] ?? 1) / $weight : 0.0);
        }

        $padX = 4.0;
        $padY = 3.5;
        $lh = $size * 1.3;

        $this->ensure(2 * ($lh + 2 * $padY) + 6);
        $this->tableHeader($cols, $widths, $size, $lh);

        if (count($rows) === 0) {
            $this->rect($this->ml, $this->y, $tw, 24, null, [0.82, 0.835, 0.859]);
            $msg = $this->enc('Tidak ada data.');
            $this->text($this->ml + ($tw - $this->width($msg, $size, false)) / 2, $this->y + 15, $msg, $size, false, [0.42, 0.44, 0.47]);
            $this->y += 24 + 8;
            return;
        }

        foreach (array_values($rows) as $i => $row) {
            $cells = [];
            $maxLines = 1;
            foreach ($cols as $j => $c) {
                $value = isset($row[$j]) ? (string) $row[$j] : '';
                $lines = $this->wrap($this->enc($value), $widths[$j] - 2 * $padX, $size, false);
                $cells[$j] = $lines;
                $maxLines = max($maxLines, count($lines));
            }
            $h = $maxLines * $lh + 2 * $padY;

            if ($this->y + $h > $this->ph - $this->mb) {
                $this->newPage();
                $this->tableHeader($cols, $widths, $size, $lh);
            }

            $x = $this->ml;
            foreach ($cols as $j => $c) {
                $this->rect($x, $this->y, $widths[$j], $h, $i % 2 === 1 ? [0.953, 0.957, 0.965] : null, [0.82, 0.835, 0.859]);
                foreach ($cells[$j] as $k => $line) {
                    $this->cellText($x, $widths[$j], $this->y + $padY + $size * 0.85 + $k * $lh, $line, $size, false, (string) ($c['align'] ?? 'L'), [0.14, 0.15, 0.17], $padX);
                }
                $x += $widths[$j];
            }
            $this->y += $h;
        }
        $this->y += 8;
    }

    // ------------------------------------------------------------------ keluaran

    /** Isi file PDF (biner). */
    public function output(): string
    {
        $pages = $this->pages;
        $pages[] = implode("\n", $this->cur);
        $n = count($pages);

        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $next = 5;
        $imgRes = '';
        if ($this->logoData !== null) {
            $objs[5] = '<< /Type /XObject /Subtype /Image /Width ' . $this->logoW . ' /Height ' . $this->logoH
                . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($this->logoData) . " >>\nstream\n"
                . $this->logoData . "\nendstream";
            $imgRes = ' /XObject << /Im1 5 0 R >>';
            $next = 6;
        }

        $kids = [];
        foreach ($pages as $i => $content) {
            $content .= "\n" . $this->footer($i + 1, $n);
            $contentNo = $next;
            $pageNo = $next + 1;
            $next += 2;
            $objs[$contentNo] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
            $objs[$pageNo] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $this->f($this->pw) . ' ' . $this->f($this->ph)
                . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>' . $imgRes . ' >> /Contents ' . $contentNo . ' 0 R >>';
            $kids[] = $pageNo . ' 0 R';
        }
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';

        ksort($objs);
        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objs as $no => $body) {
            $offsets[$no] = strlen($out);
            $out .= $no . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xref = strlen($out);
        $max = max(array_keys($objs));
        $out .= "xref\n0 " . ($max + 1) . "\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $out .= sprintf('%010d', $offsets[$i]) . " 00000 n \n";
        }
        $out .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $out;
    }

    /** Respons unduh PDF langsung (bukan tampil di tab baru). */
    public function download(string $filename)
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'laporan.pdf';

        return response($this->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'private, no-store, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }

    // ------------------------------------------------------------------ halaman

    private function startPage(bool $first = false): void
    {
        $this->cur = [];
        $this->y = $this->mt;
        if ($first) {
            $this->letterhead();
        }
    }

    private function newPage(): void
    {
        $this->pages[] = implode("\n", $this->cur);
        $this->startPage(false);
    }

    private function ensure(float $h): void
    {
        if ($this->y + $h > $this->ph - $this->mb) {
            $this->newPage();
        }
    }

    private function letterhead(): void
    {
        $x = $this->ml;
        if ($this->logoData !== null) {
            $s = 38.0;
            $this->cur[] = 'q ' . $this->f($s) . ' 0 0 ' . $this->f($s) . ' ' . $this->f($this->ml) . ' ' . $this->f($this->py($this->y + $s)) . ' cm /Im1 Do Q';
            $x = $this->ml + $s + 10;
        }
        $this->text($x, $this->y + 15, $this->enc('SMK NEGERI 1 BANTUL'), 14, true);
        $this->text($x, $this->y + 29, $this->enc('School Class Progress (SCP) - Presensi, Jurnal Mengajar & Perizinan Siswa'), 8.5, false, [0.42, 0.44, 0.47]);
        $this->line($this->ml, $this->y + 44, $this->pw - $this->mr, $this->y + 44, [0.2, 0.21, 0.24], 1.2);
        $this->y += 58;
    }

    private function footer(int $i, int $n): string
    {
        $saved = $this->cur;
        $this->cur = [];

        $y = $this->ph - $this->mb + 14;
        $this->line($this->ml, $y - 9, $this->pw - $this->mr, $y - 9, [0.82, 0.835, 0.859], 0.5);
        $left = $this->enc('Dicetak oleh ' . $this->printedBy . ' pada ' . $this->printedAt);
        $this->text($this->ml, $y + 2, $left, 7.5, false, [0.42, 0.44, 0.47]);
        $right = $this->enc('Halaman ' . $i . ' dari ' . $n);
        $this->text($this->pw - $this->mr - $this->width($right, 7.5, false), $y + 2, $right, 7.5, false, [0.42, 0.44, 0.47]);

        $out = implode("\n", $this->cur);
        $this->cur = $saved;

        return $out;
    }

    private function tableHeader(array $cols, array $widths, float $size, float $lh): void
    {
        $padX = 4.0;
        $padY = 3.5;
        $cells = [];
        $maxLines = 1;
        foreach ($cols as $j => $c) {
            $lines = $this->wrap($this->enc((string) ($c['label'] ?? '')), $widths[$j] - 2 * $padX, $size, true);
            $cells[$j] = $lines;
            $maxLines = max($maxLines, count($lines));
        }
        $hh = $maxLines * $lh + 2 * $padY;

        $x = $this->ml;
        foreach ($cols as $j => $c) {
            $this->rect($x, $this->y, $widths[$j], $hh, [0.294, 0.333, 0.388], [0.294, 0.333, 0.388]);
            foreach ($cells[$j] as $k => $line) {
                $this->cellText($x, $widths[$j], $this->y + $padY + $size * 0.85 + $k * $lh, $line, $size, true, (string) ($c['align'] ?? 'L'), [1, 1, 1], $padX);
            }
            $x += $widths[$j];
        }
        $this->y += $hh;
    }

    private function cellText(float $x, float $w, float $base, string $b, float $size, bool $bold, string $align, array $col, float $padX): void
    {
        if ($align === 'R') {
            $tx = $x + $w - $padX - $this->width($b, $size, $bold);
        } elseif ($align === 'C') {
            $tx = $x + ($w - $this->width($b, $size, $bold)) / 2;
        } else {
            $tx = $x + $padX;
        }
        $this->text($tx, $base, $b, $size, $bold, $col);
    }

    // ------------------------------------------------------------------ primitif gambar

    /** Titik desimal selalu ".", tidak terpengaruh locale server. */
    private function f(float $n): string
    {
        return number_format($n, 2, '.', '');
    }

    private function py(float $y): float
    {
        return $this->ph - $y;
    }

    private function color(array $c, bool $stroke = false): string
    {
        return $this->f((float) $c[0]) . ' ' . $this->f((float) $c[1]) . ' ' . $this->f((float) $c[2]) . ($stroke ? ' RG' : ' rg');
    }

    private function esc(string $b): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $b);
    }

    private function text(float $x, float $base, string $b, float $size, bool $bold = false, array $col = [0.14, 0.15, 0.17]): void
    {
        $this->cur[] = 'BT ' . $this->color($col) . ' /' . ($bold ? 'F2' : 'F1') . ' ' . $this->f($size) . ' Tf '
            . $this->f($x) . ' ' . $this->f($this->py($base)) . ' Td (' . $this->esc($b) . ') Tj ET';
    }

    private function rect(float $x, float $top, float $w, float $h, ?array $fill = null, ?array $stroke = null, float $lw = 0.5): void
    {
        $op = [];
        if ($fill !== null) {
            $op[] = $this->color($fill);
        }
        if ($stroke !== null) {
            $op[] = $this->color($stroke, true);
            $op[] = $this->f($lw) . ' w';
        }
        $paint = ($fill !== null && $stroke !== null) ? 'B' : ($fill !== null ? 'f' : 'S');
        $op[] = $this->f($x) . ' ' . $this->f($this->py($top + $h)) . ' ' . $this->f($w) . ' ' . $this->f($h) . ' re ' . $paint;
        $this->cur[] = implode(' ', $op);
    }

    private function line(float $x1, float $y1, float $x2, float $y2, array $col, float $lw): void
    {
        $this->cur[] = $this->color($col, true) . ' ' . $this->f($lw) . ' w '
            . $this->f($x1) . ' ' . $this->f($this->py($y1)) . ' m '
            . $this->f($x2) . ' ' . $this->f($this->py($y2)) . ' l S';
    }

    // ------------------------------------------------------------------ teks

    /** UTF-8 -> Windows-1252 (1 karakter = 1 byte); karakter kontrol dibuang. */
    private function enc(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';
        if (function_exists('mb_convert_encoding')) {
            $out = @mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
            if (is_string($out)) {
                return $out;
            }
        }
        return (string) @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
    }

    private function width(string $b, float $size, bool $bold): float
    {
        $table = $bold ? self::HELVETICA_BOLD : self::HELVETICA;
        $sum = 0;
        $len = strlen($b);
        for ($i = 0; $i < $len; $i++) {
            $sum += $table[ord($b[$i])] ?? 556;
        }
        return $sum * $size / 1000.0;
    }

    /** Pecah teks (sudah Windows-1252) menjadi baris-baris sesuai lebar; kata kepanjangan dipotong. */
    private function wrap(string $b, float $maxW, float $size, bool $bold): array
    {
        $lines = [];
        $paras = preg_split('/\r\n|\n|\r/', $b);
        if ($paras === false) {
            $paras = [$b];
        }
        foreach ($paras as $para) {
            $cur = '';
            foreach (explode(' ', $para) as $w) {
                $trial = $cur === '' ? $w : $cur . ' ' . $w;
                if ($this->width($trial, $size, $bold) <= $maxW) {
                    $cur = $trial;
                    continue;
                }
                if ($cur !== '') {
                    $lines[] = $cur;
                    $cur = '';
                }
                while ($this->width($w, $size, $bold) > $maxW && strlen($w) > 1) {
                    $cut = 1;
                    while ($cut < strlen($w) && $this->width(substr($w, 0, $cut + 1), $size, $bold) <= $maxW) {
                        $cut++;
                    }
                    $lines[] = substr($w, 0, $cut);
                    $w = substr($w, $cut);
                }
                $cur = $w;
            }
            $lines[] = $cur;
        }

        // batasi tinggi satu sel agar tidak melebihi satu halaman
        if (count($lines) > 30) {
            $lines = array_slice($lines, 0, 30);
            $last = $lines[29];
            $lines[29] = substr($last, 0, max(0, strlen($last) - 3)) . '...';
        }

        return $lines;
    }

    // Lebar huruf Helvetica (per 1000 em) menurut kode Windows-1252.
    private const HELVETICA = [
        32 => 278, 33 => 278, 34 => 355, 35 => 556, 36 => 556, 37 => 889, 38 => 667, 39 => 191, 40 => 333, 41 => 333,
        42 => 389, 43 => 584, 44 => 278, 45 => 333, 46 => 278, 47 => 278, 48 => 556, 49 => 556, 50 => 556, 51 => 556,
        52 => 556, 53 => 556, 54 => 556, 55 => 556, 56 => 556, 57 => 556, 58 => 278, 59 => 278, 60 => 584, 61 => 584,
        62 => 584, 63 => 556, 64 => 1015, 65 => 667, 66 => 667, 67 => 722, 68 => 722, 69 => 667, 70 => 611, 71 => 778,
        72 => 722, 73 => 278, 74 => 500, 75 => 667, 76 => 556, 77 => 833, 78 => 722, 79 => 778, 80 => 667, 81 => 778,
        82 => 722, 83 => 667, 84 => 611, 85 => 722, 86 => 667, 87 => 944, 88 => 667, 89 => 667, 90 => 611, 91 => 278,
        92 => 278, 93 => 278, 94 => 469, 95 => 556, 96 => 333, 97 => 556, 98 => 556, 99 => 500, 100 => 556, 101 => 556,
        102 => 278, 103 => 556, 104 => 556, 105 => 222, 106 => 222, 107 => 500, 108 => 222, 109 => 833, 110 => 556, 111 => 556,
        112 => 556, 113 => 556, 114 => 333, 115 => 500, 116 => 278, 117 => 556, 118 => 500, 119 => 722, 120 => 500, 121 => 500,
        122 => 500, 123 => 334, 124 => 260, 125 => 334, 126 => 584, 127 => 761, 128 => 556, 130 => 222, 131 => 556, 132 => 333,
        133 => 1000, 134 => 556, 135 => 556, 136 => 333, 137 => 1000, 138 => 667, 139 => 333, 140 => 1000, 142 => 611, 145 => 222,
        146 => 222, 147 => 333, 148 => 333, 149 => 350, 150 => 556, 151 => 1000, 152 => 333, 153 => 1000, 154 => 500, 155 => 333,
        156 => 944, 158 => 500, 159 => 667, 160 => 278, 161 => 333, 162 => 556, 163 => 556, 164 => 556, 165 => 556, 166 => 260,
        167 => 556, 168 => 333, 169 => 737, 170 => 370, 171 => 556, 172 => 584, 173 => 333, 174 => 737, 175 => 333, 176 => 400,
        177 => 584, 178 => 333, 179 => 333, 180 => 333, 181 => 556, 182 => 537, 183 => 278, 184 => 333, 185 => 333, 186 => 365,
        187 => 556, 188 => 834, 189 => 834, 190 => 834, 191 => 611, 192 => 667, 193 => 667, 194 => 667, 195 => 667, 196 => 667,
        197 => 667, 198 => 1000, 199 => 722, 200 => 667, 201 => 667, 202 => 667, 203 => 667, 204 => 278, 205 => 278, 206 => 278,
        207 => 278, 208 => 722, 209 => 722, 210 => 778, 211 => 778, 212 => 778, 213 => 778, 214 => 778, 215 => 584, 216 => 778,
        217 => 722, 218 => 722, 219 => 722, 220 => 722, 221 => 667, 222 => 667, 223 => 611, 224 => 556, 225 => 556, 226 => 556,
        227 => 556, 228 => 556, 229 => 556, 230 => 889, 231 => 500, 232 => 556, 233 => 556, 234 => 556, 235 => 556, 236 => 278,
        237 => 278, 238 => 278, 239 => 278, 240 => 556, 241 => 556, 242 => 556, 243 => 556, 244 => 556, 245 => 556, 246 => 556,
        247 => 584, 248 => 611, 249 => 556, 250 => 556, 251 => 556, 252 => 556, 253 => 500, 254 => 556, 255 => 500,
    ];

    private const HELVETICA_BOLD = [
        32 => 278, 33 => 333, 34 => 474, 35 => 556, 36 => 556, 37 => 889, 38 => 722, 39 => 238, 40 => 333, 41 => 333,
        42 => 389, 43 => 584, 44 => 278, 45 => 333, 46 => 278, 47 => 278, 48 => 556, 49 => 556, 50 => 556, 51 => 556,
        52 => 556, 53 => 556, 54 => 556, 55 => 556, 56 => 556, 57 => 556, 58 => 333, 59 => 333, 60 => 584, 61 => 584,
        62 => 584, 63 => 611, 64 => 975, 65 => 722, 66 => 722, 67 => 722, 68 => 722, 69 => 667, 70 => 611, 71 => 778,
        72 => 722, 73 => 278, 74 => 556, 75 => 722, 76 => 611, 77 => 833, 78 => 722, 79 => 778, 80 => 667, 81 => 778,
        82 => 722, 83 => 667, 84 => 611, 85 => 722, 86 => 667, 87 => 944, 88 => 667, 89 => 667, 90 => 611, 91 => 333,
        92 => 278, 93 => 333, 94 => 584, 95 => 556, 96 => 333, 97 => 556, 98 => 611, 99 => 556, 100 => 611, 101 => 556,
        102 => 333, 103 => 611, 104 => 611, 105 => 278, 106 => 278, 107 => 556, 108 => 278, 109 => 889, 110 => 611, 111 => 611,
        112 => 611, 113 => 611, 114 => 389, 115 => 556, 116 => 333, 117 => 611, 118 => 556, 119 => 778, 120 => 556, 121 => 556,
        122 => 500, 123 => 389, 124 => 280, 125 => 389, 126 => 584, 127 => 761, 128 => 556, 130 => 278, 131 => 556, 132 => 500,
        133 => 1000, 134 => 556, 135 => 556, 136 => 333, 137 => 1000, 138 => 667, 139 => 333, 140 => 1000, 142 => 611, 145 => 278,
        146 => 278, 147 => 500, 148 => 500, 149 => 350, 150 => 556, 151 => 1000, 152 => 333, 153 => 1000, 154 => 556, 155 => 333,
        156 => 944, 158 => 500, 159 => 667, 160 => 278, 161 => 333, 162 => 556, 163 => 556, 164 => 556, 165 => 556, 166 => 280,
        167 => 556, 168 => 333, 169 => 737, 170 => 370, 171 => 556, 172 => 584, 173 => 333, 174 => 737, 175 => 333, 176 => 400,
        177 => 584, 178 => 333, 179 => 333, 180 => 333, 181 => 611, 182 => 556, 183 => 278, 184 => 333, 185 => 333, 186 => 365,
        187 => 556, 188 => 834, 189 => 834, 190 => 834, 191 => 611, 192 => 722, 193 => 722, 194 => 722, 195 => 722, 196 => 722,
        197 => 722, 198 => 1000, 199 => 722, 200 => 667, 201 => 667, 202 => 667, 203 => 667, 204 => 278, 205 => 278, 206 => 278,
        207 => 278, 208 => 722, 209 => 722, 210 => 778, 211 => 778, 212 => 778, 213 => 778, 214 => 778, 215 => 584, 216 => 778,
        217 => 722, 218 => 722, 219 => 722, 220 => 722, 221 => 667, 222 => 667, 223 => 611, 224 => 556, 225 => 556, 226 => 556,
        227 => 556, 228 => 556, 229 => 556, 230 => 889, 231 => 556, 232 => 556, 233 => 556, 234 => 556, 235 => 556, 236 => 278,
        237 => 278, 238 => 278, 239 => 278, 240 => 611, 241 => 611, 242 => 611, 243 => 611, 244 => 611, 245 => 611, 246 => 611,
        247 => 584, 248 => 611, 249 => 611, 250 => 611, 251 => 611, 252 => 611, 253 => 556, 254 => 611, 255 => 556,
    ];
}
