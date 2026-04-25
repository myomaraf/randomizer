<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raffle {{ $raffle->raffle_id }} | Omaraf Randomizer</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #0f766e;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 28px 16px 48px;
        }

        .stack {
            display: grid;
            gap: 16px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        h1, h2 {
            margin: 0 0 12px;
        }

        h1 {
            font-size: 1.9rem;
        }

        h2 {
            font-size: 1.2rem;
        }

        p {
            margin: 0 0 8px;
            color: var(--muted);
            line-height: 1.5;
        }

        .winner {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 1.1rem;
            color: var(--accent);
            font-weight: 700;
        }

        dl {
            margin: 0;
            display: grid;
            grid-template-columns: minmax(180px, 260px) 1fr;
            gap: 8px 10px;
        }

        dt {
            color: var(--muted);
            font-weight: 600;
        }

        dd {
            margin: 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            overflow-wrap: anywhere;
        }

        .formula {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
        }

        th {
            color: var(--muted);
            font-weight: 600;
        }

        td {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            overflow-wrap: anywhere;
        }

        .actions {
            margin: 10px 0 14px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            border: 1px solid var(--accent);
            color: var(--accent);
            padding: 7px 12px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.92rem;
        }

        .btn:hover {
            background: rgba(15, 118, 110, 0.07);
        }

        .links {
            margin-top: 14px;
        }

        .top-links {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }

        .top-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 4px 8px;
            background: #fff;
            color: var(--text);
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--muted);
            font-size: 0.92rem;
        }

        @media (max-width: 760px) {
            dl {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<main class="container">
    <div class="top-links">
        <a href="/">Homepage</a>
        <a href="{{ route('raffles.index') }}">Verify another raffle</a>
    </div>

    <div class="stack">
        <section class="card">
            <h1>Raffle {{ $raffle->raffle_id }}</h1>
            <p>Winning UUID</p>
            <div class="winner">{{ $raffle->selected_uuid }}</div>
        </section>

        <section class="card">
            <h2>Audit Trail</h2>
            <dl>
                <dt>raffle_id</dt><dd>{{ $raffle->raffle_id }}</dd>
                <dt>uuids_sha256</dt><dd>{{ $raffle->uuids_sha256 }}</dd>
                <dt>nonce_hex</dt><dd>{{ $raffle->nonce_hex }}</dd>
                <dt>digest_sha256</dt><dd>{{ $raffle->digest_sha256 }}</dd>
                <dt>index_selected</dt><dd>{{ $raffle->index_selected }}</dd>
                <dt>count</dt><dd>{{ $raffle->count }}</dd>
                <dt>timestamp_utc</dt><dd>{{ $raffle->timestamp_utc?->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z') }}</dd>
                <dt>algorithm_version</dt><dd>{{ $raffle->algorithm_version }}</dd>
            </dl>
        </section>

        <section class="card">
            <h2>How It Was Calculated</h2>
            <p>1. Canonicalize all ticket UUIDs (trim + lowercase).</p>
            <p>2. Sort UUIDs lexicographically.</p>
            <p>3. Build digest input: joined_sorted_uuids + ":" + raffle_id + ":" + nonce_hex.</p>
            <p>4. Compute digest_sha256 = SHA-256(digest_input).</p>
            <p>5. Take first 16 hex chars as a 64-bit seed, compute index_selected = seed % count.</p>
            <p>6. selected_uuid = sorted_uuids[index_selected].</p>
            <div class="formula">
                digest_sha256 = sha256(joined_sorted_uuids : raffle_id : nonce_hex)<br>
                index_selected = int(first_16_hex_of_digest, base16) % count
            </div>
        </section>

        <section class="card">
            <h2>Tickets</h2>
            <p>Total tickets: {{ $raffle->count }}. Loaded in chunks from API for performance.</p>
            <div class="actions">
                <a class="btn" href="{{ route('raffles.tickets.export.csv', ['raffle_id' => $raffle->raffle_id]) }}">
                    Export Tickets CSV
                </a>
                <a class="btn" href="{{ route('raffles.tickets.export.xls', ['raffle_id' => $raffle->raffle_id]) }}">
                    Export Tickets Excel
                </a>
            </div>
            <table id="tickets-table">
                <thead>
                <tr>
                    <th>Position</th>
                    <th>UUID</th>
                </tr>
                </thead>
            </table>
        </section>
    </div>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $(function () {
        $('#tickets-table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            lengthMenu: [
                [25, 50, 100, 250, 500],
                [25, 50, 100, 250, 500],
            ],
            ajax: '{{ route('raffles.tickets.data', ['raffle_id' => $raffle->raffle_id]) }}',
            order: [[0, 'asc']],
            columns: [
                { data: 'position', name: 'position' },
                { data: 'uuid', name: 'uuid' },
            ],
            language: {
                search: 'Search UUID or position:',
                lengthMenu: 'Show _MENU_ tickets',
                info: 'Showing _START_ to _END_ of _TOTAL_ tickets',
                infoFiltered: '(filtered from _MAX_ total)',
            },
        });
    });
</script>
</body>
</html>
