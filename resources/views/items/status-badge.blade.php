@php
    if ($item->published()) {
        $label = 'Published';
        $class = 'success';
    } elseif ($item->pending()) {
        $label = 'Pending Review';
        $class = 'warning';
    } elseif ($item->changesRequired()) {
        $label = 'Changes Requested';
        $class = 'danger';
    } else {
        $label = 'Draft';
        $class = 'secondary';
    }
@endphp

<span class="badge badge-{{ $class }}">{{ $label }}</span>
