<table class="table table-sm">
    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Description') }}</th><th class="text-end">{{ __('Charges') }}</th><th class="text-end">{{ __('Credits') }}</th></tr></thead>
    <tbody>
    @foreach ($lines as $l)
        @php $t = $l->total(); @endphp
        <tr><td dir="ltr">{{ $l->business_date->toDateString() }}</td><td>{{ $l->description }}</td><td class="text-end">@if ($t > 0)<x-money :value="$t" />@endif</td><td class="text-end">@if ($t < 0)<x-money :value="-$t" />@endif</td></tr>
    @endforeach
    </tbody>
</table>
