{{-- Okvir za formu šifarnika; sadržaj se dovlači sa servera. --}}
<x-drawer title="" state="formDrawer" title-expr="formTitle">
    <div x-show="formLoading" class="flex justify-center py-10">
        <div class="h-6 w-6 border-2 border-primary/30 border-t-primary rounded-full animate-spin"></div>
    </div>
    <div x-show="! formLoading" x-html="formHtml"></div>
</x-drawer>
