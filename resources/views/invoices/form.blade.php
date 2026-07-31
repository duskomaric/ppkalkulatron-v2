{{-- Tijelo drawera sa formom; servira ga InvoiceController@create i @edit na ?partial=1. --}}
<x-invoices.form :invoice="$invoice" :clients="$clients" :articles="$articles" :currencies="$currencies"
                 :default-template="$defaultTemplate" :default-currency="$defaultCurrency"
                 :default-due-days="$defaultDueDays" :default-notes="$defaultNotes" />
