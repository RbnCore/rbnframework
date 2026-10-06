<!DOCTYPE html>
<?php
// R-05: mesaj/dosya/satır/trace `htmlspecialchars(ENT_QUOTES|ENT_SUBSTITUTE, UTF-8)
// ile basılır (XSS + yol sızıntısı kapandı). Üretimde (`RBN_DEV` yok/false) hiçbir
// ayrıntı gösterilmez: yalnız genel mesaj + hata kimliği (üretilir, log'a yazılır).
$rbnDev = defined('RBN_DEV') && RBN_DEV === true;
$rbnId = (isset($errorId) && is_string($errorId) && $errorId !== '') ? $errorId : strtoupper(bin2hex(random_bytes(4)));
error_log('[RBN-PANIC] id=' . $rbnId . ' | ' . (string) ($message ?? '') . ' @ ' . (string) ($file ?? '') . ':' . (string) ($line ?? ''));
$rbnE = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$rbnMsg = $rbnDev ? $rbnE($message) : 'Sistem şu an kullanılamıyor.';
$rbnMeta = $rbnDev ? 'At <b>' . $rbnE($file) . '</b> (Line <b>' . $rbnE($line) . '</b>)' : 'Hata kimliği: <b>' . $rbnE($rbnId) . '</b>';
$rbnTrace = $rbnDev ? $rbnE($trace) : '(ayrıntı yalnız sunucu günlüğünde)';
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBN Framework | Boot Failure 🏛️⚓</title>
    <style>
        :root {
            --bg: #0d0d0d;
            --card: #141414;
            --accent: #ff4d4d;
            --text-main: #f0f0f0;
            --text-dim: #a0a0a0;
            --border: #222222;
        }

        body {
            margin: 0;
            padding: 40px;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container {
            width: 100%;
            max-width: 900px;
            background-color: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            background-color: var(--accent);
            padding: 25px 40px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid rgba(0,0,0,0.1);
        }

        .header-text h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 900;
        }

        .header-text p {
            margin: 3px 0 0;
            opacity: 0.8;
            font-size: 13px;
        }

        .header .btn-copy-top {
            background-color: #000;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 10px 22px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .header .btn-copy-top:hover {
            background-color: #1a1a1a;
            border-color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }

        .content {
            padding: 40px;
        }

        .error-box {
            border-left: 4px solid var(--accent);
            padding: 20px;
            background-color: rgba(255, 77, 77, 0.05);
            margin-bottom: 30px;
        }

        .error-message {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 10px;
            word-wrap: break-word;
        }

        .error-meta {
            color: var(--text-dim);
            font-size: 14px;
        }

        .error-meta b {
            color: var(--accent);
        }

        h2 {
            font-size: 16px;
            text-transform: uppercase;
            color: var(--text-dim);
            margin: 30px 0 15px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 5px;
        }

        .trace {
            background-color: #000;
            padding: 20px;
            border-radius: 4px;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #72cc72;
            overflow-x: auto;
            white-space: pre-wrap;
            border: 1px solid var(--border);
        }

        .footer {
            padding: 20px 40px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0,0,0,0.2);
        }

        .btn-copy {
            background-color: var(--accent);
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: opacity 0.2s;
        }

        .btn-copy:hover {
            opacity: 0.8;
        }

        .footer-text {
            font-size: 12px;
            color: var(--text-dim);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-text">
                <h1>Critical Boot Failure 🏛️⚔️🛡️⚓</h1>
                <p>RBN Framework Sentinel (Fail-Safe Mode)</p>
            </div>
            <button class="btn-copy btn-copy-top" onclick="copyError()">Copy Error 📋</button>
        </div>
        <div class="content">
            <div class="error-box">
                <div class="error-message" id="err-msg"><?php echo $rbnMsg; ?></div>
                <div class="error-meta"><?php echo $rbnMeta; ?></div>
            </div>

            <h2>Stack Trace</h2>
            <div class="trace" id="err-trace"><?php echo $rbnTrace; ?></div>
        </div>
        <div class="footer">
            <button class="btn-copy" onclick="copyError()">Copy Error 📋</button>
            <div class="footer-text">RbnShield 3.5 | Autonomous Emergency Interface ✅🛡️⚓</div>
        </div>
    </div>

    <script>
        function copyError() {
            const msg = document.getElementById('err-msg').innerText;
            const trace = document.getElementById('err-trace').innerText;
            const text = "RBN BOOT FAILURE\n================\nMessage: " + msg + "\nTrace:\n" + trace;
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(setCopied);
            } else {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                textArea.style.top = "0";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    setCopied();
                } catch (err) {
                    console.error('Copy failed', err);
                    alert('Copy failed. Please select and copy manually.');
                }
                document.body.removeChild(textArea);
            }

            function setCopied() {
                const btns = document.querySelectorAll('.btn-copy');
                btns.forEach(btn => {
                    const original = btn.innerText;
                    btn.innerText = 'COPIED! ✅';
                    setTimeout(() => { btn.innerText = original; }, 2000);
                });
            }
        }
    </script>
</body>
</html>
