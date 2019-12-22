@extends('admin.layouts.admin')

@section('title', trans('admin.themes.title'))

@push('footer-scripts')
    <script>
        document.querySelectorAll('[data-route]').forEach(function (el) {
            el.addEventListener('click', function (ev) {
                ev.preventDefault();

                const themeForm = document.getElementById('themeForm');

                themeForm.setAttribute('action', el.dataset['route']);
                themeForm.submit();
            });
        });
    </script>
@endpush

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">{{ trans('admin.themes.current.title') }}</h6>
        </div>
        <div class="card-body">
            @if($current)
                <h3 class="h5">{{ $current->name }}</h3>
                <ul>
                    <li>{{ trans('admin.themes.current.author', ['author' => join(', ', $current->authors)]) }}</li>
                    <li>{{ trans('admin.themes.current.version', ['version' => $current->version]) }}</li>
                </ul>

                <form action="{{ route('admin.themes.change') }}" method="POST">
                    @csrf

                    <a class="btn btn-primary" href="{{ route('admin.themes.edit', $currentPath) }}"><i class="fas fa-wrench"></i> {{ trans('admin.themes.actions.edit-config') }}</a>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-times"></i> {{ trans('admin.themes.actions.disable') }}</button>
                </form>
            @else
                {{ trans('admin.themes.no-enabled') }}
            @endif
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">{{ trans('admin.themes.installed') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">{{ trans('messages.fields.name') }}</th>
                        <th scope="col">{{ trans('messages.fields.author') }}</th>
                        <th scope="col">{{ trans('messages.fields.version') }}</th>
                        <th scope="col">{{ trans('messages.fields.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($themes as $path => $theme)
                        <tr>
                            <th scope="row">{{ $theme->name }}</th>
                            <td>{{ join(', ', $theme->authors) }}</td>
                            <td>{{ $theme->version }}</td>
                            <td>
                                <button type="button" class="btn btn-primary" data-route="{{ route('admin.themes.change', $path) }}"><i class="fas fa-check"></i> {{ trans('messages.actions.enable') }}</button>
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form id="themeForm" method="POST" class="d-none">
        @csrf
    </form>
@endsection
