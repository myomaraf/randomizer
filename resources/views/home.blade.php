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

        .title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 12px;
        }

        h1 {
            margin: 0;
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

        .verify-link {
            display: inline-block;
            margin-top: 14px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
        }

        .github-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--border);
            color: var(--text);
            background: #f9fafb;
            text-decoration: none;
        }

        .github-link:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        .github-icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }
    </style>
</head>
<body>
<main class="wrap">
    <section class="card">
        <div class="title-row">
            <h1>Omaraf Randomizer</h1>
            <a
                class="github-link"
                href="https://github.com/myomaraf/randomizer"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="View source code on GitHub"
                title="GitHub repository"
            >
                <svg class="github-icon" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8 0C3.58 0 0 3.58 0 8a8 8 0 0 0 5.47 7.59c.4.07.55-.17.55-.38l-.01-1.49c-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.5-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82A7.56 7.56 0 0 1 8 4.67c.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48l-.01 2.2c0 .21.15.46.55.38A8 8 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/>
                </svg>
            </a>
        </div>
        <p>
            Open-source UUID randomizer API that accepts an array of UUIDs and returns exactly one selected UUID
            using a transparent and auditable algorithm.
        </p>
        <p>
            The API canonicalizes and sorts ticket UUIDs, mixes them with raffle_id and a cryptographic nonce, then
            stores a complete audit trail so each selection can be independently reviewed.
        </p>
        <div class="endpoint">POST /api/randomize</div>
        <a class="verify-link" href="/raffles">Verify a raffle</a>
    </section>
</main>
</body>
</html>
