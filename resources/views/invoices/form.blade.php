{{-- Zajedničko tijelo forme za create i edit stranice. --}}
<x-invoices.form :invoice="$invoice" :clients="$clients" :articles="$articles" :currencies="$currencies"
                 :default-template="$defaultTemplate" :default-language="$defaultLanguage" :default-currency="$defaultCurrency"
                 :currency-symbols="$currencySymbols" :default-due-days="$defaultDueDays" :default-notes="$defaultNotes"
                 :default-payment-type="$defaultPaymentType" />
