<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store">
    <title>Refreshing session</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        p { opacity: .85; }
    </style>
</head>
<body>
    <p>Refreshing your session…</p>
    <script>
        (function () {
            var path = window.location.pathname || '';
            var host = window.location.host;
            var referrer = document.referrer || '';
            var sameHost = referrer.indexOf(host) !== -1;

            if (path.indexOf('/login') === 0) {
                window.location.replace('/login');
                return;
            }

            if (sameHost && referrer.indexOf('/login') === -1) {
                window.location.replace(referrer);
                return;
            }

            window.location.replace('/');
        })();
    </script>
</body>
</html>
