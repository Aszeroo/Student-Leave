<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'front' ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'navbar.php' ?>
    <?php include 'sidebar.php' ?>

    <div class="content">
        <?php if (isset($content)) echo $content; ?>
    </div>

    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
