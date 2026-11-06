{{-- Badge status seragam. Pemakaian: @include('partials.status', ['type' => 'attendance'|'leave', 'value' => $status, 'suffix' => ': 3' (opsional)]) --}}
@php
    $maps = [
        'attendance' => [
            'hadir'           => ['Hadir', 'success'],
            'sakit'           => ['Sakit', 'info'],
            'izin'            => ['Izin', 'info'],
            'izin_tengah_jam' => ['Izin Tengah Jam', 'info'],
            'dispensasi'      => ['Dispensasi', 'muted'],
            'alpha'           => ['Alpha', 'danger'],
        ],
        'leave' => [
            'pending'  => ['Pending', 'warning'],
            'approved' => ['Approved', 'success'],
            'rejected' => ['Rejected', 'danger'],
            'revisi'   => ['Revisi', 'info'],
        ],
    ];
    [$__label, $__tone] = $maps[$type][$value] ?? [ucfirst(str_replace('_', ' ', (string) $value)), 'muted'];
@endphp
<span class="scp-badge is-{{ $__tone }}">{{ $__label }}{{ $suffix ?? '' }}</span>
