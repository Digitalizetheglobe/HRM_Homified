<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('NOC') }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            color: #000000;
            font-size: 14px;
            line-height: 1.6;
        }
        .content-box {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="content-box">
        {!! $noc_certificate->content !!}
    </div>
</body>
</html>