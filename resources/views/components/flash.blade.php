{{--
    Poruke iz sesije se predaju toastu, kao u v1 — nema statične trake iznad sadržaja.
--}}
@if (session('status') || session('error'))
    @php($detail = ['message' => session('status') ?: session('error'), 'type' => session('status') ? 'success' : 'error'])

    <script>
        document.addEventListener('alpine:initialized', () => {
            window.dispatchEvent(new CustomEvent('app-flash', { detail: @js($detail) }));
        });
    </script>
@endif
