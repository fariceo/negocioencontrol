<?php
if(isset($_POST['status'])) {
    $status = $_POST['status'];
    $status_file = "../bot_status.txt";
    file_put_contents($status_file, $status);
}
header("Location: panel.php");
exit;

//cd /var/www/elpollovolantuso/bot
//python3 bot_iqoption.py
?>