<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?php echo $ENCODING; ?>" />
    <title><?php echo $VERSION; ?>: <?php echo $active; ?></title>
    <link rel="stylesheet" type="text/css" href="theme.css" />
</head>
<body>
<div class="row">
    <div class="sidebar">
    </div>
    <div class="main">
        <h1><?php echo $active; ?></h1>
        <?php foreach ($results as $i=>$result): ?>
        <p>
            <span class="status <?php echo $result['status']?'pass':'fail'; ?>"><?php echo $i+1; ?></span>
            <span class="text"><?php echo $result['text']; ?> <?php if (!$result['status'] && $result['source']) echo '('.$result['source'].')' ?></span><br/>
        </p>
        <?php endforeach ?>
    </div>
</div>
</body>
</html>