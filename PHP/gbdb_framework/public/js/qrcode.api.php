<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR-Code</title>
    <script type="text/javascript" src="../js/qr.min.js"></script>
    <script type="text/javascript" src="../js/jquerry.min.js"></script>
    <script type="text/javascript">
    var zielurl_standard = 'https://greenbucket.online'; //.replace(/\/g/, '%2F').replace(/\:/g, '%3A');
    var width_standard = 200;
    var heigth_standard = 200;
    var correctlevel_standard = 'H';

    function hole_linkparameter(welcheurl, parameter) {
        if (welcheurl.match(/\%2F/)) {
            welcheurl = welcheurl.replace(/\%2F/g, '/');
        }

        if (welcheurl.match(/\%3F/)) {
            welcheurl = welcheurl.replace(/\%3F/g, '?');
        }

        if (welcheurl.match(/\&amp;/)) {
            welcheurl = welcheurl.replace(/\&amp;/g, '&');
        }

        if (welcheurl.match(/\?/)) {

            if (welcheurl.match(String(parameter))) {

                var ziellink_zu_lang = welcheurl.split('zielurl=')[1];
                var ziellink = ziellink_zu_lang.split(/\&/)[0];
                var width_zu_lang = welcheurl.split('width=')[1];
                var width = width_zu_lang.split(/\&/)[0];
                var height_zu_lang = welcheurl.split('height=')[1];
                var height = height_zu_lang.split(/\&/)[0];
                var correctlevel_zu_lang = welcheurl.split('correctlevel=')[1];
                var correctlevel = correctlevel_zu_lang.split(/\&/)[0];

                switch (parameter) {
                    case 'zielurl':
                        return ziellink
                    case 'width':
                        return width
                    case 'height':
                        return height
                    case 'correctlevel':
                        return correctlevel
                }
            } else {
                console.log('Fehler: Parameter ' + parameter + ' fehlt');
            }
        } else {
            console.log('Fehler: ? fehlt');
        }
    }


    function lade_seite() {
        var url_string = window.location.href;
        var url = new URL(url_string);

        zielurl = hole_linkparameter(url_string, 'zielurl');
        console.log('zielurl: ' + zielurl);

        width = hole_linkparameter(url_string, 'width');
        console.log('width: ' + width);

        height = hole_linkparameter(url_string, 'height');
        console.log('height: ' + height);

        correctlevel = hole_linkparameter(url_string, 'correctlevel');
        console.log('correctlevel: ' + correctlevel);

        if (zielurl) {
            if (zielurl.length > 2) {
                if (width, height) {
                    if (correctlevel) {
                        $('div#qrcode').css('width', width + 'px').css('height', height + 'px')
                        erstelle_qrcode(zielurl, width, height, correctlevel);

                    } else {
                        $('div#qrcode').html('Kein Korrekturlevel angegeben!');
                    }
                } else {
                    $('div#qrcode').html('Keine Breite und/oder Höhe angegeben!');
                }
            } else {
                $('div#qrcode').html('');
                $('div#qrcode').html('Zieladresse zu kurz!');
            }
        } else {
            $('div#qrcode').html('');
            $('div#qrcode').html('keine Zieladresse übergeben');
        }
    }

    function erstelle_qrcode(zielurl, width, height, correctlevel) {
        $('div#qrcode').html('');

        switch (correctlevel) {
            case 'L':
                new QRCode(document.getElementById('qrcode'), {
                    text: zielurl,
                    width: width,
                    height: height,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.L
                });
                break
            case 'M':
                new QRCode(document.getElementById('qrcode'), {
                    text: zielurl,
                    width: width,
                    height: height,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
                break
            case 'Q':
                new QRCode(document.getElementById('qrcode'), {
                    text: zielurl,
                    width: width,
                    height: height,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.Q
                });
                break
            case 'H':
                new QRCode(document.getElementById('qrcode'), {
                    text: zielurl,
                    width: width,
                    height: height,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
                break
            default:
        }
    }

    $(document).ready(function() {
        lade_seite();
    });
    </script>
    <style>
    body {
        margin: 0px;
        padding: 0px;
    }

    div#qrcode {
        margin: 0px;
        padding: 0px;
    }

    img {
        margin: 0px;
        padding: 0px;
    }

    canvas {
        margin: 0px;
        padding: 0px;
    }
    </style>
</head>

<body>
    <div id="qrcode"></div>
</body>

</html>
