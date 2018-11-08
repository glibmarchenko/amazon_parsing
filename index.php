<?php
    include_once ('./library/simple_html_dom.php');
    include_once './class/AmazonParser.php';
    $amazonUrl = $_GET['form_url'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Amazon Parsing</title>
    <link rel="stylesheet" type="text/css" href="./assets/style.css">
</head>
<body>

<div class="container">
    <h2>Amazon Parsing</h2>
    <form method="get" action="index.php" class="form">
        <div class="form__field">
            <label for="form_url">Amazon product url:</label>
            <input type="text" id="form_url" name="form_url">
        </div>
        <div class="form__field">
            <input type="submit" value="Get Data">
        </div>
    </form>

    <?php if ($amazonUrl) : ?>
    <main>
        <hr>
        <h4>Product Data: <?= $amazonUrl ?></h4>
        <div>
            <?php
                var_dump(AmazonParser::getAmazonData($amazonUrl))
            ?>
        </div>
    </main>
    <?php endif; ?>

</div>

</body>
</html>
