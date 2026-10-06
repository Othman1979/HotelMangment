@props(['value', 'sign' => false])
@php $v = round((float) $value, 3); @endphp
<span {{ $attributes->class(['font-monospace text-nowrap', 'text-danger' => $sign && $v > 0, 'text-success' => $sign && $v < 0]) }} dir="ltr">{{ number_format($v, 3) }}</span>
