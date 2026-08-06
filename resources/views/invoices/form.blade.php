{{-- Zajedničko tijelo forme za create i edit stranice. --}}
<x-invoices.form :invoice="$invoice" :clients="$clients" :articles="$articles" :currencies="$currencies"
                 :default-language="$defaultLanguage" :default-currency="$defaultCurrency"
                 :currency-symbols="$currencySymbols" :exchange-rates="$exchangeRates" :default-due-days="$defaultDueDays" :default-notes="$defaultNotes"
                 :default-payment-type="$defaultPaymentType" />
