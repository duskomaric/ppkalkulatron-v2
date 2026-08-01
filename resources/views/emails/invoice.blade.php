<x-emails.layout :title="'Faktura '.$invoice->invoice_number" :subtitle="$company->name">
    <div style="color:#344054;font-size:15px;line-height:1.7;">{!! nl2br(e($body)) !!}</div>
    @if ($verificationUrl)
        <p style="margin:24px 0 0;">
            <a href="{{ $verificationUrl }}" style="display:inline-block;padding:12px 20px;background:#f59e0b;color:#ffffff!important;font-size:14px;font-weight:600;text-decoration:none;border-radius:8px;">Verifikacija fiskalnog računa</a>
        </p>
    @endif
</x-emails.layout>
