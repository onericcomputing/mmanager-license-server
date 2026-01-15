<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #333; }
        .container { padding: 20px; }
        .title { text-align: center; font-size: 18pt; font-weight: bold; margin: 20px 0; color: {{ $template['primary_color'] ?? '#059669' }}; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: {{ $template['primary_color'] ?? '#059669' }}; color: white; padding: 10px; text-align: left; font-size: 9pt; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .totals { width: 300px; margin-left: auto; margin-top: 20px; }
        .totals tr td { padding: 5px 10px; }
        .totals .total { font-weight: bold; font-size: 12pt; background: #f5f5f5; }
        .notes { margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .notes h4 { font-size: 9pt; color: #666; margin-bottom: 5px; }
        .validity { margin-top: 20px; padding: 10px; background: #fef3c7; border-left: 4px solid #f59e0b; }
        .footer { margin-top: 40px; text-align: center; font-size: 8pt; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <table style="margin-bottom: 30px;">
        <tr>
            <td style="width: 50%; vertical-align: top; border: none;">
                @if($logo)
                <img src="{{ $logo }}" style="max-height: 60px;">
                @endif
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top; border: none;">
                <h1 style="font-size: 14pt; margin-bottom: 5px;">{{ $company['name'] ?? '' }}</h1>
                <p>{{ $company['address'] ?? '' }}</p>
                <p>{{ $company['postal_code'] ?? '' }} {{ $company['city'] ?? '' }}</p>
                @if(!empty($company['phone']))<p>{{ $company['phone'] }}</p>@endif
                @if(!empty($company['email']))<p>{{ $company['email'] }}</p>@endif
            </td>
        </tr>
    </table>

    <!-- Title -->
    <h2 class="title">DEVIS {{ $data['number'] ?? '' }}</h2>

    <!-- Info boxes -->
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top; border: none;">
                <h3 style="font-size: 9pt; color: #666; margin-bottom: 5px;">DESTINATAIRE</h3>
                <p><strong>{{ $client['company'] ?? $client['name'] ?? '' }}</strong></p>
                @if(!empty($client['name']) && !empty($client['company']))<p>{{ $client['name'] }}</p>@endif
                <p>{{ $client['address'] ?? '' }}</p>
                <p>{{ $client['postal_code'] ?? '' }} {{ $client['city'] ?? '' }}</p>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top; border: none;">
                <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($data['date'] ?? now())->format('d/m/Y') }}</p>
                @if(!empty($data['valid_until']))
                <p><strong>Valide jusqu'au:</strong> {{ \Carbon\Carbon::parse($data['valid_until'])->format('d/m/Y') }}</p>
                @endif
                @if(!empty($data['reference']))
                <p><strong>Référence:</strong> {{ $data['reference'] }}</p>
                @endif
            </td>
        </tr>
    </table>

    <!-- Items table -->
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Description</th>
                <th class="text-right">Qté</th>
                <th class="text-right">Prix unit.</th>
                <th class="text-right">TVA</th>
                <th class="text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <strong>{{ $item['name'] ?? '' }}</strong>
                    @if(!empty($item['description']))
                    <br><small style="color: #666;">{{ $item['description'] }}</small>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item['quantity'] ?? 0, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($item['unit_price'] ?? 0, 2, ',', ' ') }} €</td>
                <td class="text-right">{{ number_format($item['tax_rate'] ?? 0, 0) }}%</td>
                <td class="text-right">{{ number_format($item['subtotal'] ?? 0, 2, ',', ' ') }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals">
        <tr>
            <td>Sous-total HT</td>
            <td class="text-right">{{ number_format($data['subtotal'] ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        @if(($data['discount_amount'] ?? 0) > 0)
        <tr>
            <td>Remise</td>
            <td class="text-right">-{{ number_format($data['discount_amount'], 2, ',', ' ') }} €</td>
        </tr>
        @endif
        <tr>
            <td>TVA</td>
            <td class="text-right">{{ number_format($data['tax_amount'] ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        <tr class="total">
            <td><strong>Total TTC</strong></td>
            <td class="text-right"><strong>{{ number_format($data['total'] ?? 0, 2, ',', ' ') }} €</strong></td>
        </tr>
    </table>

    <!-- Validity notice -->
    @if(!empty($data['valid_until']))
    <div class="validity">
        <strong>Ce devis est valable jusqu'au {{ \Carbon\Carbon::parse($data['valid_until'])->format('d/m/Y') }}.</strong>
    </div>
    @endif

    <!-- Notes -->
    @if(!empty($data['notes']))
    <div class="notes">
        <h4>Notes</h4>
        <p>{{ $data['notes'] }}</p>
    </div>
    @endif

    @if(!empty($data['terms']))
    <div class="notes" style="margin-top: 10px;">
        <h4>Conditions</h4>
        <p>{{ $data['terms'] }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>{{ $company['name'] ?? '' }}</p>
    </div>
</div>
</body>
</html>
