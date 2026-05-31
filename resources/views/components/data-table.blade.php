@props(['columns', 'paginator' => null, 'empty' => 'No se encontraron registros.', 'colspan' => null])

@php
    $span = $colspan ?? count($columns);
@endphp

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if ($paginator && $paginator->count())
                {{ $slot }}
            @else
                <tr>
                    <td colspan="{{ $span }}">{{ $empty }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

@if ($paginator)
    <div class="pagination-wrap">
        {{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@endif
