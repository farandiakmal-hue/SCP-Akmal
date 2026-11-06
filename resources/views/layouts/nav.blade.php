@php
    use App\Models\Role;
    $role = auth()->user()->role_id ?? null;

    $items = [
        ['dashboard', 'Dashboard', 'bi-speedometer2', null],
        ['schedule.index', 'Jadwal', 'bi-calendar3', [Role::WAKA, Role::GURU, Role::WALI_KELAS, Role::GURU_BK]],
        ['journal.index', 'Jurnal', 'bi-journal-text', [Role::WAKA, Role::GURU, Role::WALI_KELAS, Role::GURU_BK]],
        ['attendance.mine', 'Presensi', 'bi-calendar-check', [Role::SISWA, Role::ORTU]],
        ['leave.index', 'Izin', 'bi-file-earmark-medical', [Role::SISWA, Role::ORTU, Role::WALI_KELAS, Role::GURU_BK, Role::WAKA]],
        ['reports.attendance', 'Rekap', 'bi-bar-chart-line', [Role::WAKA, Role::WALI_KELAS, Role::GURU_BK]],
        ['reports.leave.daily', 'Izin Harian', 'bi-calendar-x', [Role::WALI_KELAS, Role::GURU_BK]],
        ['users.index', 'Manajemen Data', 'bi-gear', [Role::WAKA]],
        ['profile.edit', 'Profil', 'bi-person-circle', null],
    ];

    $visible = collect($items)->filter(fn ($i) => $i[3] === null || in_array($role, $i[3]));
@endphp

@if(($variant ?? 'sidebar') === 'bottom')
    @foreach($visible as [$routeName, $label, $icon, $roles])
        <a href="{{ route($routeName) }}" class="{{ request()->routeIs($routeName) ? 'active' : '' }}">
            <i class="bi {{ $icon }}"></i>{{ $label }}
        </a>
    @endforeach
@else
    @foreach($visible as [$routeName, $label, $icon, $roles])
        <a href="{{ route($routeName) }}" class="scp-nav-link {{ request()->routeIs($routeName) ? 'active' : '' }}">
            <i class="bi {{ $icon }}"></i> {{ $label }}
        </a>
    @endforeach
@endif
