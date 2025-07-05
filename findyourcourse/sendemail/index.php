<?
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Send Email</title>
</head>

<body>
    <form class="" action="send.php" method="post">
        Email <input type="email" name="email" value=""> <br>
        Subject <input type="text" name="sujet" value=""> <br>
        Message <input type="text" name="message" value=""> <br>
        <button type="submit" name="send">Send</button>
    </form>
</body>

</html>