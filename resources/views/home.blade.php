<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Omaraf Randomizer</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f8fa;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --accent: #0f766e;
            --border: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background: radial-gradient(circle at top, #ecfeff 0%, var(--bg) 50%);
            color: var(--text);
        }

        .wrap {
            max-width: 760px;
            margin: 0 auto;
            padding: 48px 20px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        h1 {
            margin: 0 0 12px;
            font-size: 2rem;
        }

        p {
            margin: 0 0 12px;
            line-height: 1.6;
            color: var(--muted);
        }

        code, pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .endpoint {
            margin-top: 16px;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #f9fafb;
            color: var(--accent);
            font-weight: 600;
        }
    </style>
</head>
<body>
<main class="wrap">
    <section class="card">
        <h1>Omaraf Randomizer</h1>
        <p>
            Open-source UUID randomizer API that accepts an array of UUIDs and returns exactly one selected UUID
            using a transparent and auditable algorithm.
        </p>
        <p>
            The API canonicalizes and sorts UUIDs, mixes a time bucket with a cryptographic nonce, and returns an
            audit trail so each selection can be independently reviewed.
        </p>
        <div class="endpoint">POST /api/randomize</div>
    </section>
</main>
</body>
</html>
