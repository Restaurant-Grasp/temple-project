<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            background: linear-gradient(135deg, #8b2500 0%, #b8621b 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: #fff;
            border: 2px solid #8b4513;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }
        
        .summary-number {
            font-size: 28px;
            font-weight: bold;
            color: #8b4513;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        
        table thead {
            background: #8b4513;
            color: white;
        }
        
        table thead th {
            padding: 10px 5px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #6d3410;
        }
        
        table tbody td {
            padding: 8px 5px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        table tbody tr:hover {
            background: #f0f0f0;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-relocate {
            background: #ffc107;
            color: #000;
        }
        
        .badge-swap {
            background: #17a2b8;
            color: white;
        }
        
        .badge-create {
            background: #28a745;
            color: white;
        }
        
        .badge-update {
            background: #007bff;
            color: white;
        }
        
        .badge-cancel {
            background: #dc3545;
            color: white;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #666;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .text-muted {
            color: #999;
        }
        
        .text-center {
            text-align: center;
        }
        
        .location-info {
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generated on {{ $generated_at }}</p>
    </div>

    <!-- Report Information -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Total Records:</span>
            <span>{{ count($records) }}</span>
        </div>
        
        @if(isset($filters['event_name']))
        <div class="info-row">
            <span class="info-label">Event:</span>
            <span>{{ $filters['event_name'] }}</span>
        </div>
        @endif
        
        @if(isset($filters['start_date']) && isset($filters['end_date']))
        <div class="info-row">
            <span class="info-label">Date Range:</span>
            <span>{{ date('d/m/Y', strtotime($filters['start_date'])) }} to {{ date('d/m/Y', strtotime($filters['end_date'])) }}</span>
        </div>
        @endif
        
        @if(isset($filters['action_type']))
        <div class="info-row">
            <span class="info-label">Action Type:</span>
            <span>{{ $filters['action_type'] }}</span>
        </div>
        @endif
    </div>

    <!-- Summary Statistics -->
    @if(isset($summary))
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-number">{{ $summary['total_relocations'] }}</div>
            <div class="summary-label">Total Relocations</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-number">{{ $summary['by_action_type']['RELOCATE'] ?? 0 }}</div>
            <div class="summary-label">Direct Relocations</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-number">{{ $summary['by_action_type']['SWAP'] ?? 0 }}</div>
            <div class="summary-label">Swaps</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-number">{{ $summary['by_action_type']['UPDATE'] ?? 0 }}</div>
            <div class="summary-label">Updates</div>
        </div>
    </div>
    @endif

    <!-- Relocation Records Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Date & Time</th>
                <th style="width: 15%;">Event</th>
                <th style="width: 10%;">Booking #</th>
                <th style="width: 15%;">Old Location</th>
                <th style="width: 15%;">New Location</th>
                <th style="width: 8%;">Action</th>
                <th style="width: 15%;">Reason</th>
                <th style="width: 10%;">Changed By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
            <tr>
                <td>{{ \Carbon\Carbon::parse($record->changed_at)->format('d/m/Y H:i') }}</td>
                <td>{{ $record->event_name ?? 'N/A' }}</td>
                <td>{{ $record->booking_number ?? 'N/A' }}</td>
                <td>
                    @if($record->old_table_name)
                        <strong>{{ $record->old_table_name }}</strong><br>
                        <span class="location-info">
                            {{ $record->old_assign_number ?? 'N/A' }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if($record->new_table_name)
                        <strong>{{ $record->new_table_name }}</strong><br>
                        <span class="location-info">
                            {{ $record->new_assign_number ?? 'N/A' }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge badge-{{ strtolower($record->action_type) }}">
                        {{ $record->action_type }}
                    </span>
                </td>
                <td>
                    <small>{{ $record->change_reason ?? 'N/A' }}</small>
                </td>
                <td>{{ $record->changed_by_name ?? 'System' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted">No relocation records found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Temple Management System - Relocation Log Report - Page <span class="pagenum"></span></p>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 9;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>