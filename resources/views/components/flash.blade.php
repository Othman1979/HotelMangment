@foreach (['ok' => 'success', 'err' => 'danger'] as $key => $type)
    @if (session($key))
        <div class="alert alert-{{ $type }} alert-dismissible fade show" role="alert">
            {{ __(session($key), $key === 'ok' ? session('ok_replace', []) : []) }}
            @if ($key === 'ok' && session('print'))
                <a href="{{ session('print') }}" target="_blank" class="ms-2 fw-semibold">{{ __('Print') }}</a>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
