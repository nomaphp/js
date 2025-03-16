<?php

require_once __DIR__ . '/../../vendor/autoload.php';
$js = \Noma\Js\Js::fromFile(__DIR__ . '/react.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>React Example</title>
</head>
<body>
<div id="root"></div>

<script type="module">
    <?php echo $js; ?>
</script>

</body>
</html>