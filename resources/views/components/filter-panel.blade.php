@props(['action'])

<form method="GET" action="{{ $action }}" class="toolbar" data-ajax-filter>
    {{ $slot }}

    <div class="actions toolbar-actions">
        {{ $actions ?? '' }}
    </div>
</form>
