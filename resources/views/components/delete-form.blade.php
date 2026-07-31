@props(['action', 'confirm' => 'Obrisati zapis?'])

<form id="delete-entity" method="POST" action="{{ $action }}" class="hidden"
      onsubmit="return confirm(@js($confirm))">
    @csrf
    @method('DELETE')
</form>
