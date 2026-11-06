<?php

namespace App\Support;

class JamSesi
{
    // Seluruh sesi: [no => ['no'=>, 'start'=>'07:15', 'end'=>'07:50']]
    public static function all(): array
    {
        $out = [];
        foreach (config('jam_sesi.sesi') as $no => [$start, $end]) {
            $out[(int) $no] = ['no' => (int) $no, 'start' => $start, 'end' => $end];
        }
        return $out;
    }

    public static function find(int $no): ?array
    {
        return self::all()[$no] ?? null;
    }

    public static function breaks(): array
    {
        return config('jam_sesi.istirahat');
    }

    // Label "Sesi 1" / "Sesi 1-2" bila jam jadwal tepat sesuai batas sesi, selain itu null
    public static function label(string $start, string $end): ?string
    {
        $start = substr($start, 0, 5);
        $end = substr($end, 0, 5);
        $first = $last = null;

        foreach (self::all() as $s) {
            if ($s['start'] === $start) $first = $s['no'];
            if ($s['end'] === $end) $last = $s['no'];
        }

        if ($first === null || $last === null || $last < $first) {
            return null;
        }
        return $first === $last ? "Sesi {$first}" : "Sesi {$first}-{$last}";
    }
}
