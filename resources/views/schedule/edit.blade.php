@extends('layouts.app')
@section('title', 'Ubah Jadwal')

@section('content')
    <div class="scp-page-head d-flex justify-content-between align-items-start">
        <div>
            <h1 class="scp-page-title">Ubah Jadwal</h1>
            <div class="scp-page-sub">{{ $schedule->cycle_type }} &middot; {{ ucfirst($schedule->day_of_week) }} {{ substr($schedule->start_time,0,5) }}-{{ substr($schedule->end_time,0,5) }}</div>
        </div>
        <a href="{{ route('schedule.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>

    <div class="scp-card">
        <div class="scp-card-header">Ubah Data Jadwal</div>
        <div class="p-3">
            @include('schedule._form', [
                'action' => route('schedule.update', $schedule->id),
                'method' => 'PUT',
                'submit' => 'Simpan Perubahan',
            ])
        </div>
    </div>
@endsection
