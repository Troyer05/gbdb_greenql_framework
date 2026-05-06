<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BAR-Code: <?php echo $_GET["value"]; ?></title>
</head>

<body>
    <svg id="barcode"></svg>
</body>

<script src="../js/bar.min.js"></script>
<script>
JsBarcode("#barcode", "<?php echo $_GET["value"]; ?>");
</script>

</html>
