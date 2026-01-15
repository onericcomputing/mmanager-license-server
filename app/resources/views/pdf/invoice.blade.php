<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ ($data['is_credit_note'] ?? false) ? 'Avoir' : 'Facture' }} {{ $data['number'] ?? '' }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.5;
            color: #1a1a2e;
            background: #ffffff;
            padding: 40px 50px;
        }

        @php
            $isCredit = $data['is_credit_note'] ?? false;
            $primaryColor = $isCredit ? '#dc2626' : ($template['primary_color'] ?? '#0f172a');
            $accentColor = $isCredit ? '#fef2f2' : '#f1f5f9';
            $borderColor = $isCredit ? '#fecaca' : '#e2e8f0';
        @endphp

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid {{ $primaryColor }};
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
            text-align: right;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: {{ $primaryColor }};
            margin-bottom: 3px;
        }

        .company-contact {
            font-size: 8pt;
            color: #64748b;
        }

        .document-badge {
            display: inline-block;
            background: {{ $accentColor }};
            border: 2px solid {{ $borderColor }};
            border-radius: 8px;
            padding: 12px 20px;
            text-align: center;
        }

        .document-type {
            font-size: 8pt;
            font-weight: 700;
            color: {{ $primaryColor }};
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .document-number {
            font-size: 14pt;
            font-weight: bold;
            color: {{ $primaryColor }};
            margin-top: 2px;
        }

        /* Info Section */
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .info-card {
            display: table-cell;
            vertical-align: top;
            padding-right: 20px;
        }

        .info-card:last-child {
            padding-right: 0;
        }

        .info-label {
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: {{ $primaryColor }};
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .client-name {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .info-text {
            font-size: 8pt;
            color: #64748b;
            line-height: 1.6;
        }

        .info-highlight {
            font-size: 7pt;
            color: #94a3b8;
            margin-top: 5px;
            font-style: italic;
        }

        /* Meta Grid */
        .meta-grid {
            display: table;
            width: 100%;
        }

        .meta-item {
            display: table-row;
        }

        .meta-label {
            display: table-cell;
            font-size: 8pt;
            color: #94a3b8;
            padding: 4px 15px 4px 0;
        }

        .meta-value {
            display: table-cell;
            font-size: 8pt;
            font-weight: 600;
            color: #1e293b;
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .status-draft { background: #f1f5f9; color: #64748b; }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-unpaid { background: #fef3c7; color: #b45309; }
        .status-partially_paid { background: #fed7aa; color: #c2410c; }
        .status-paid { background: #d1fae5; color: #047857; }
        .status-overdue { background: #fee2e2; color: #b91c1c; }
        .status-cancelled { background: #f1f5f9; color: #64748b; }
        .status-refunded { background: #ede9fe; color: #6d28d9; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table thead tr {
            background: {{ $primaryColor }};
        }

        .items-table th {
            padding: 12px 15px;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #ffffff;
            text-align: left;
        }

        .items-table th.text-right { text-align: right; }
        .items-table th.text-center { text-align: center; }

        .items-table td {
            padding: 14px 15px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            font-size: 8pt;
        }

        .items-table td.text-right { text-align: right; }
        .items-table td.text-center { text-align: center; }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #e2e8f0;
        }

        .item-name {
            font-size: 9pt;
            font-weight: 600;
            color: #0f172a;
        }

        .item-description {
            font-size: 8pt;
            color: #64748b;
            margin-top: 3px;
        }

        .item-ref {
            font-size: 7pt;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Totals */
        .totals-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .totals-spacer {
            display: table-cell;
            width: 55%;
        }

        .totals-box {
            display: table-cell;
            width: 45%;
        }

        .totals-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 18px;
            border: 1px solid #e2e8f0;
        }

        .totals-line {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .totals-line:last-child {
            margin-bottom: 0;
        }

        .totals-label {
            display: table-cell;
            font-size: 8pt;
            color: #64748b;
        }

        .totals-value {
            display: table-cell;
            font-size: 8pt;
            font-weight: 600;
            color: #334155;
            text-align: right;
        }

        .totals-discount .totals-value {
            color: #dc2626;
        }

        .totals-grand {
            background: {{ $primaryColor }};
            margin: 15px -18px -18px -18px;
            padding: 15px 18px;
            border-radius: 0 0 10px 10px;
        }

        .totals-grand .totals-label {
            font-size: 10pt;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
        }

        .totals-grand .totals-value {
            font-size: 14pt;
            font-weight: 700;
            color: #ffffff;
        }

        /* Payment Info Box */
        .payment-summary {
            margin-top: 12px;
            padding: 12px 15px;
            border-radius: 8px;
        }

        .payment-summary-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
        }

        .payment-summary-pending {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        /* Payment History */
        .payment-history {
            margin-top: 20px;
            padding: 15px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
        }

        .payment-history-title {
            font-size: 8pt;
            font-weight: 700;
            color: #0369a1;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payment-item {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .payment-item:last-child {
            margin-bottom: 0;
        }

        .payment-detail {
            display: table-cell;
            font-size: 8pt;
            color: #64748b;
            padding: 3px 0;
        }

        .payment-amount {
            display: table-cell;
            font-size: 8pt;
            font-weight: 600;
            text-align: right;
            padding: 3px 0;
        }

        .payment-amount-positive { color: #059669; }
        .payment-amount-negative { color: #dc2626; }

        /* Bank Info */
        .bank-info {
            margin-top: 20px;
            padding: 15px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
        }

        .bank-title {
            font-size: 8pt;
            font-weight: 700;
            color: #0369a1;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bank-grid {
            display: table;
            width: 100%;
        }

        .bank-row {
            display: table-row;
        }

        .bank-label {
            display: table-cell;
            font-size: 8pt;
            color: #0369a1;
            padding: 3px 15px 3px 0;
            width: 80px;
        }

        .bank-value {
            display: table-cell;
            font-size: 8pt;
            font-weight: 600;
            color: #0c4a6e;
        }

        .bank-iban {
            font-family: 'DejaVu Sans Mono', monospace;
            letter-spacing: 1px;
        }

        /* Notes */
        .notes-section {
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .notes-cell {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .notes-cell:last-child {
            padding-right: 0;
            padding-left: 10px;
        }

        .notes-box {
            background: #fafafa;
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #e5e7eb;
        }

        .notes-title {
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .notes-content {
            font-size: 8pt;
            color: #475569;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        /* Footer */
        .footer-section {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .legal-text {
            font-size: 7pt;
            color: #94a3b8;
            line-height: 1.5;
            text-align: center;
            margin-bottom: 15px;
        }

        .company-legal {
            display: table;
            width: 100%;
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 15px;
        }

        .legal-item {
            display: table-cell;
            text-align: center;
            font-size: 7pt;
            color: #64748b;
            border-right: 1px solid #e2e8f0;
            padding: 0 15px;
        }

        .legal-item:last-child {
            border-right: none;
        }

        .legal-label {
            color: #94a3b8;
            display: block;
            margin-bottom: 2px;
        }

        .legal-value {
            font-weight: 600;
            color: #475569;
        }

        /* Watermarks */
        @if(($data['status']['value'] ?? '') === 'paid')
        .watermark {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            font-weight: bold;
            color: rgba(34, 197, 94, 0.06);
            text-transform: uppercase;
            white-space: nowrap;
            z-index: -1;
        }
        @endif

        @if(($data['status']['value'] ?? '') === 'cancelled')
        .watermark {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60pt;
            font-weight: bold;
            color: rgba(220, 38, 38, 0.08);
            text-transform: uppercase;
            white-space: nowrap;
            z-index: -1;
        }
        @endif

        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $isCredit = $data['is_credit_note'] ?? false;
        $statusValue = is_array($data['status'] ?? null) ? ($data['status']['value'] ?? 'draft') : ($data['status'] ?? 'draft');
    @endphp

    {{-- Watermark --}}
    @if($statusValue === 'paid')
        <div class="watermark">PAYEE</div>
    @elseif($statusValue === 'cancelled')
        <div class="watermark">ANNULEE</div>
    @endif

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            @if($logo)
                <img src="{{ $logo }}" style="max-height: 50px; margin-bottom: 8px;">
            @endif
            <div class="company-name">{{ $company['company_name'] ?? $company['name'] ?? 'Mon Entreprise' }}</div>
            <div class="company-contact">
                @if(!empty($company['company_email'] ?? $company['email'])){{ $company['company_email'] ?? $company['email'] }}@endif
                @if(!empty($company['company_phone'] ?? $company['phone'])) | {{ $company['company_phone'] ?? $company['phone'] }}@endif
            </div>
        </div>
        <div class="header-right">
            <div class="document-badge">
                <div class="document-type">{{ $isCredit ? 'Avoir' : ($data['document_type_label'] ?? 'Facture') }}</div>
                <div class="document-number">{{ $data['number'] ?? 'BROUILLON' }}</div>
            </div>
        </div>
    </div>

    {{-- Info Row --}}
    <div class="info-row">
        {{-- Client --}}
        <div class="info-card" style="width: 40%;">
            <div class="info-label">{{ $isCredit ? 'Avoir pour' : 'Facturer a' }}</div>
            <div class="client-name">{{ $client['company'] ?? $client['name'] ?? '' }}</div>
            <div class="info-text">
                @if(!empty($client['address'])){{ $client['address'] }}<br>@endif
                @if(!empty($client['postal_code']) || !empty($client['city'])){{ $client['postal_code'] ?? '' }} {{ $client['city'] ?? '' }}<br>@endif
                @if(!empty($client['country'])){{ $client['country'] }}@endif
            </div>
            @if(!empty($client['vat_number']))
                <div class="info-highlight">TVA: {{ $client['vat_number'] }}</div>
            @endif
        </div>

        {{-- Emetteur --}}
        <div class="info-card" style="width: 30%;">
            <div class="info-label">Emetteur</div>
            <div class="info-text">
                @if(!empty($company['company_name'] ?? $company['name'])){{ $company['company_name'] ?? $company['name'] }}<br>@endif
                @if(!empty($company['company_address'] ?? $company['address'])){{ $company['company_address'] ?? $company['address'] }}<br>@endif
                @if(!empty($company['company_postal_code'] ?? $company['postal_code']) || !empty($company['company_city'] ?? $company['city'])){{ $company['company_postal_code'] ?? $company['postal_code'] ?? '' }} {{ $company['company_city'] ?? $company['city'] ?? '' }}<br>@endif
                @if(!empty($company['company_country'] ?? $company['country'])){{ $company['company_country'] ?? $company['country'] }}@endif
            </div>
        </div>

        {{-- Details --}}
        <div class="info-card" style="width: 30%; text-align: right;">
            <div class="info-label" style="text-align: right;">Details</div>
            <div class="meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Date</span>
                    <span class="meta-value">{{ \Carbon\Carbon::parse($data['date'] ?? now())->format('d/m/Y') }}</span>
                </div>
                @if(!$isCredit && !empty($data['due_date']))
                <div class="meta-item">
                    <span class="meta-label">Echeance</span>
                    <span class="meta-value">{{ \Carbon\Carbon::parse($data['due_date'])->format('d/m/Y') }}</span>
                </div>
                @endif
                @if(!empty($data['reference']))
                <div class="meta-item">
                    <span class="meta-label">Reference</span>
                    <span class="meta-value">{{ $data['reference'] }}</span>
                </div>
                @endif
                @if(!empty($data['po_number']))
                <div class="meta-item">
                    <span class="meta-label">Bon de cmd</span>
                    <span class="meta-value">{{ $data['po_number'] }}</span>
                </div>
                @endif
            </div>
            @php
                $statusLabel = is_array($data['status'] ?? null) ? ($data['status']['label'] ?? 'Brouillon') : ucfirst($data['status'] ?? 'brouillon');
            @endphp
            <span class="status-badge status-{{ $statusValue }}">{{ $statusLabel }}</span>
        </div>
    </div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 44%;">Description</th>
                <th class="text-center" style="width: 12%;">Qte</th>
                <th class="text-right" style="width: 14%;">P.U. HT</th>
                <th class="text-center" style="width: 10%;">TVA</th>
                <th class="text-right" style="width: 20%;">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <div class="item-name">{{ $item['name'] ?? '' }}</div>
                    @if(!empty($item['description']))<div class="item-description">{{ $item['description'] }}</div>@endif
                    @if(!empty($item['reference']))<div class="item-ref">Ref: {{ $item['reference'] }}</div>@endif
                </td>
                <td class="text-center">{{ number_format($item['quantity'] ?? 0, 2, ',', ' ') }} {{ $item['unit'] ?? '' }}</td>
                <td class="text-right">{{ number_format($item['unit_price'] ?? 0, 2, ',', ' ') }} EUR</td>
                <td class="text-center">{{ number_format($item['tax_rate'] ?? 0, 1) }}%</td>
                <td class="text-right" style="font-weight: 600;">{{ number_format($item['subtotal'] ?? 0, 2, ',', ' ') }} EUR</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals-row avoid-break">
        <div class="totals-spacer"></div>
        <div class="totals-box">
            <div class="totals-card">
                <div class="totals-line">
                    <span class="totals-label">Sous-total HT</span>
                    <span class="totals-value">{{ number_format($data['subtotal'] ?? 0, 2, ',', ' ') }} EUR</span>
                </div>
                @if(($data['discount_amount'] ?? 0) > 0)
                <div class="totals-line totals-discount">
                    <span class="totals-label">Remise</span>
                    <span class="totals-value">-{{ number_format($data['discount_amount'], 2, ',', ' ') }} EUR</span>
                </div>
                @endif
                <div class="totals-line">
                    <span class="totals-label">TVA</span>
                    <span class="totals-value">{{ number_format($data['tax_amount'] ?? 0, 2, ',', ' ') }} EUR</span>
                </div>
                <div class="totals-grand">
                    <div class="totals-line">
                        <span class="totals-label">Total TTC</span>
                        <span class="totals-value">{{ number_format($data['total'] ?? 0, 2, ',', ' ') }} EUR</span>
                    </div>
                </div>
            </div>

            {{-- Payment Summary --}}
            @if(!$isCredit && ($data['amount_paid'] ?? 0) > 0)
            <div class="payment-summary {{ ($data['amount_due'] ?? 0) <= 0 ? 'payment-summary-success' : 'payment-summary-pending' }}">
                <div class="totals-line" style="margin-bottom: 4px;">
                    <span class="totals-label" style="color: #64748b; font-size: 8pt;">Total regle</span>
                    <span class="totals-value" style="color: #059669; font-size: 9pt;">{{ number_format($data['amount_paid'], 2, ',', ' ') }} EUR</span>
                </div>
                @if(($data['amount_due'] ?? 0) > 0)
                <div class="totals-line">
                    <span class="totals-label" style="color: #b91c1c; font-size: 8pt; font-weight: 600;">Reste a payer</span>
                    <span class="totals-value" style="color: #dc2626; font-size: 11pt; font-weight: 700;">{{ number_format($data['amount_due'], 2, ',', ' ') }} EUR</span>
                </div>
                @else
                <div class="totals-line">
                    <span class="totals-label" style="color: #047857; font-size: 8pt; font-weight: 700;">SOLDE</span>
                    <span class="totals-value" style="color: #047857; font-size: 10pt; font-weight: 700;">0,00 EUR</span>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- Payment History --}}
    @if(!empty($payments) && count($payments) > 0)
    <div class="payment-history avoid-break">
        <div class="payment-history-title">Historique des paiements</div>
        @foreach($payments as $payment)
        <div class="payment-item">
            <span class="payment-detail">
                {{ \Carbon\Carbon::parse($payment['date'])->format('d/m/Y') }}
                @if(!empty($payment['method'])) - {{ $payment['method'] }}@endif
                @if(!empty($payment['reference'])) <span style="color: #94a3b8;">({{ $payment['reference'] }})</span>@endif
                @if(!empty($payment['notes'])) <span style="color: #64748b; font-style: italic;"> - {{ $payment['notes'] }}</span>@endif
            </span>
            <span class="payment-amount {{ ($payment['is_refund'] ?? false) ? 'payment-amount-negative' : 'payment-amount-positive' }}">
                {{ ($payment['is_refund'] ?? false) ? '-' : '+' }}{{ number_format($payment['amount'] ?? 0, 2, ',', ' ') }} EUR
            </span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Bank Info --}}
    @php
        $paymentSettings = $settings['payment'] ?? $settings ?? [];
        $hasPaymentInfo = !empty($paymentSettings['payment_iban']) || !empty($paymentSettings['payment_bank_name']);
    @endphp
    @if(!$isCredit && $hasPaymentInfo && ($data['amount_due'] ?? $data['total'] ?? 0) > 0)
    <div class="bank-info avoid-break">
        <div class="bank-title">Informations de paiement</div>
        <div class="bank-grid">
            @if(!empty($paymentSettings['payment_bank_name']))
            <div class="bank-row">
                <span class="bank-label">Banque</span>
                <span class="bank-value">{{ $paymentSettings['payment_bank_name'] }}</span>
            </div>
            @endif
            @if(!empty($paymentSettings['payment_iban']))
            <div class="bank-row">
                <span class="bank-label">IBAN</span>
                <span class="bank-value bank-iban">{{ $paymentSettings['payment_iban'] }}</span>
            </div>
            @endif
            @if(!empty($paymentSettings['payment_bic']))
            <div class="bank-row">
                <span class="bank-label">BIC</span>
                <span class="bank-value">{{ $paymentSettings['payment_bic'] }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Notes & Terms --}}
    @if(!empty($data['notes']) || !empty($data['terms']))
    <div class="notes-section avoid-break">
        @if(!empty($data['notes']))
        <div class="notes-cell" style="{{ empty($data['terms']) ? 'width: 100%;' : '' }}">
            <div class="notes-box">
                <div class="notes-title">Notes</div>
                <div class="notes-content">{{ $data['notes'] }}</div>
            </div>
        </div>
        @endif
        @if(!empty($data['terms']))
        <div class="notes-cell" style="{{ empty($data['notes']) ? 'width: 100%; padding-left: 0;' : '' }}">
            <div class="notes-box">
                <div class="notes-title">Conditions</div>
                <div class="notes-content">{{ $data['terms'] }}</div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Legal Mentions --}}
    @if(!empty($data['legal_mentions']) && count($data['legal_mentions']) > 0)
    <div style="margin-top: 15px; padding: 10px 12px; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px;">
        <div style="font-size: 7pt; font-weight: 700; color: #92400e; margin-bottom: 5px; text-transform: uppercase;">Mentions legales</div>
        @foreach($data['legal_mentions'] as $mention)
        <div style="font-size: 7pt; color: #78350f; margin-bottom: 2px;">{{ $mention }}</div>
        @endforeach
    </div>
    @endif

    {{-- Footer --}}
    @php
        $hasLegalInfo = !empty($company['company_siret'] ?? $company['siret']) || !empty($company['company_vat_number'] ?? $company['vat_number']) || !empty($company['company_rcs'] ?? $company['rcs']);
    @endphp
    @if($hasLegalInfo)
    <div class="footer-section avoid-break">
        @if(!$isCredit && empty($data['terms']))
        <div class="legal-text">
            En cas de retard de paiement, une penalite de 3 fois le taux d'interet legal sera appliquee,
            ainsi qu'une indemnite forfaitaire de 40 EUR pour frais de recouvrement.
        </div>
        @endif

        <div class="company-legal">
            @if(!empty($company['company_siret'] ?? $company['siret']))
            <div class="legal-item">
                <span class="legal-label">SIRET</span>
                <span class="legal-value">{{ $company['company_siret'] ?? $company['siret'] }}</span>
            </div>
            @endif
            @if(!empty($company['company_vat_number'] ?? $company['vat_number']))
            <div class="legal-item">
                <span class="legal-label">TVA</span>
                <span class="legal-value">{{ $company['company_vat_number'] ?? $company['vat_number'] }}</span>
            </div>
            @endif
            @if(!empty($company['company_rcs'] ?? $company['rcs']))
            <div class="legal-item">
                <span class="legal-label">RCS</span>
                <span class="legal-value">{{ $company['company_rcs'] ?? $company['rcs'] }}</span>
            </div>
            @endif
            @if(!empty($company['company_capital'] ?? $company['capital']))
            <div class="legal-item">
                <span class="legal-label">Capital</span>
                <span class="legal-value">{{ $company['company_capital'] ?? $company['capital'] }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif
</body>
</html>
