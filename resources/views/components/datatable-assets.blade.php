@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/horalia/css/datatables.css') }}">
    @endpush
    @push('scripts')
        <script defer src="{{ asset('assets/vendor/datatables/dataTables.min.js') }}"></script>
        <script defer src="{{ asset('assets/horalia/js/datatables.js') }}"></script>
    @endpush
@endonce
