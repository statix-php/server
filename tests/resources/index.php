<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible"
          content="IE=edge">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Test HTML Document</title>
</head>

<body>
    <h1>Test PHP Script</h1>
    <ul>Env Variables</ul>
    
        <?php foreach ($envVars as $key => $value) : ?>
            <li><?php echo $key ?>: <?php echo $value ?></li>
        <?php endforeach; ?>
    
    </ul>

    <p>Timestamp: <?php echo time(); ?></p>
    
</body>

</html>