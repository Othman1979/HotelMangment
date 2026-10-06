@props(['room'])
@php
    $code = $room->statusCode();
    $color = match ($code) { 'VC', 'VI' => 'success', 'VD' => 'warning', 'OC', 'OI' => 'primary', 'OD' => 'info', default => 'danger' };
@endphp
<span {{ $attributes->class(['badge', 'text-bg-'.$color]) }} title="{{ $room->housekeeping_status->label() }} / {{ $room->occupancy_status->label() }} / {{ $room->service_status->label() }}">{{ $code }}</span>
