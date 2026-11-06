<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Label & format teks untuk laporan PDF (bahasa Indonesia).
 */
class PdfLabels
{
    /** Batas baris per PDF agar server hosting bersama tidak kehabisan memori/waktu. */
    public const MAX_ROWS = 5000;

    public static function attendance($value): string
    {
        $map = [
            'hadir'           => 'Hadir',
            'sakit'           => 'Sakit',
            'izin'            => 'Izin',
            'izin_tengah_jam' => 'Izin Tengah Jam',
            'dispensasi'      => 'Dispensasi',
            'alpha'           => 'Alpha',
        ];

        return $map[$value] ?? ucfirst(str_replace('_', ' ', (string) $value));
    }

    public static function leaveStatus($value): string
    {
        $map = [
            'pending'  => 'Pending',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'revisi'   => 'Revisi',
        ];

        return $map[$value] ?? ucfirst((string) $value);
    }

    public static function leaveType($value): string
    {
        $map = [
            'sakit'           => 'Sakit',
            'izin'            => 'Izin',
            'izin_tengah_jam' => 'Izin Tengah Jam',
            'dispensasi'      => 'Dispensasi',
        ];

        return $map[$value] ?? ucfirst(str_replace('_', ' ', (string) $value));
    }

    /** Tanggal berbahasa Indonesia, mis. "06 Nov 2026" atau (format 'l, d F Y') "Jumat, 06 November 2026". */
    public static function date($value, string $format = 'd M Y'): string
    {
        if (empty($value)) {
            return '-';
        }
        try {
            return Carbon::parse($value)->locale('id')->translatedFormat($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /** "07:15:00" -> "07:15" */
    public static function time($value): string
    {
        return empty($value) ? '-' : substr((string) $value, 0, 5);
    }

    public static function period($start, $end): string
    {
        $hasStart = !empty($start);
        $hasEnd = !empty($end);

        if (!$hasStart && !$hasEnd) {
            return 'Semua tanggal';
        }
        if ($hasStart && $hasEnd) {
            return self::date($start) . ' s/d ' . self::date($end);
        }

        return $hasStart ? 'Sejak ' . self::date($start) : 'Sampai ' . self::date($end);
    }

    public static function printedBy(): string
    {
        $user = auth()->user();
        if (!$user) {
            return '-';
        }

        return ($user->fullname ?? $user->username) . ' (' . $user->roleLabel() . ')';
    }

    /** "Hadir 10  |  Sakit 2  |  ..." untuk baris ringkasan. */
    public static function summaryLine(array $statuses, $counts, callable $label): string
    {
        $parts = [];
        foreach ($statuses as $st) {
            $parts[] = $label($st) . ' ' . ($counts[$st] ?? 0);
        }

        return implode('  |  ', $parts);
    }
}
