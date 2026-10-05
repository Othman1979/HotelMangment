@props(['status'])
<span {{ $attributes->class(['badge', 'text-bg-'.(method_exists($status, 'color') ? $status->color() : 'secondary')]) }}>{{ $status->label() }}</span>
