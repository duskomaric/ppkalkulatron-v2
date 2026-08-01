@props(['action', 'confirm' => 'Obrisati zapis?'])

<form id="delete-entity" method="POST" action="{{ $action }}" class="hidden" data-confirm="{{ $confirm }}">
    @csrf
    @method('DELETE')
</form>
