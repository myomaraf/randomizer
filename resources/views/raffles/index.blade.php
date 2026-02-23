<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Raffle | Omaraf Randomizer</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --accent: #0f766e;
            --border: #e2e8f0;
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
            max-width: 680px;
            margin: 0 auto;
            padding: 32px 16px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 1.8rem;
        }

        p {
            margin: 0 0 16px;
            color: var(--muted);
            line-height: 1.5;
        }

        form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        input[type="text"] {
            flex: 1 1 360px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 1rem;
        }

        button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        .home-link {
            display: inline-block;
            margin-top: 16px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
<main class="container">
    <section class="card">
        <h1>Verify a Raffle</h1>
        <p>Enter a raffle ID to view the winner, audit fields, and full ticket list.</p>
        <form method="GET" action="{{ route('raffles.index') }}">
            <input
                type="text"
                name="raffle_id"
                placeholder="raffle_001"
                maxlength="100"
                required
            >
            <button type="submit">Open Raffle</button>
        </form>
        <a class="home-link" href="/">Back to homepage</a>
    </section>
</main>
</body>
</html>
