<html>
    <head>
        <?php Header("Cache-Control: max-age=3000, must-revalidate"); ?>
        <title>Secret Staff Chat</title>
        <link rel="stylesheet" href="chatstyle.css?v=<?php echo time(); ?>">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#b91d47">
        <meta name="theme-color" content="#ffffff">
    </head>
    <body>
        
        <?php
            class MyDB extends SQLite3 {
                function __construct() {
                $this->open('../Databases.db');
                }
            }

            $md5Pass = "";
            if (isset($_POST["pass"])){
                $pass = $_POST["pass"];
                $md5Pass = md5($pass);
            } else if (isset($_POST["token"])){
                $md5Pass = $_POST["token"];
            }

            if ($md5Pass != "") 
            {
                if (!isset($_POST['name'])){
                    echo <<<EOT
                    <div id="hideBox">
                    <div id="nameInput">
                        <form id="nameForm" method="POST">
                            <label for="name">Please enter your name:</label><br>
                            <input type="text" name="name">
                            <input type="submit">
                    EOT;
                    echo "<input name=\"token\" type=\"hidden\" value=\"".$md5Pass."\">";
                    echo "</form></div></div>";
                    
                }
                $db = new MyDB();
                $cmd = $db->prepare("SELECT * FROM INFO WHERE NAME='ChatPass' AND VALUE = :pass");
                $cmd->bindValue(':pass',$md5Pass);
                $res = $cmd->execute();
                $ret = $res->fetchArray();
                if ($ret){
                    $res->finalize();
                    if (isset($_POST['name']) && isset($_POST['message'])){
                        $cmd = $db->prepare("INSERT INTO messages(name,message,datetime) VALUES(:name,:msg,datetime('now'))");
                        $cmd->bindValue(':msg', $_POST['message']);
                        $cmd->bindValue(':name', $_POST['name']);
                        $res = $cmd->execute();
                        $res->finalize();
                    }


                    $ret = $db->query("SELECT * FROM MESSAGES ORDER BY DATETIME");
                    
                    echo "<div id=\"messages\">";
                    while ($row = $ret->fetchArray(SQLITE3_ASSOC)){
                        echo "<div id=\"msg\">";
                        echo "<div id=\"msgHeader\">";
                        echo "<b class=\"name\">".$row['Name']."</b> ";
                        echo "<p class=\"time\" class=\"time\">".$row['DateTime']."</p></div>";
                        echo "<p class=\"message\">".$row['Message']."</p> ";
                        echo "</div>";
                    }
                }
                else {
                    echo "<p style=\"color:black;\">Password invalid. Click <a href=\"VVViewer.php\">here</a> to return to the main page.</p>";
                }

            } else {
                echo "<p style=\"color:black;\">Password invalid. Click <a href=\"VVViewer.php\">here</a> to return to the main page.</p>";
            }
        ?>
        </div>
        <form id="textBox" action="chat.php" method="POST">
            <input name="token" type="hidden" value="<?php global $md5Pass; echo $md5Pass ?>">
            <input name="name" type="hidden" value="<?php if (isset($_POST['name'])) echo $_POST['name'] ?>">
            <textarea id="message" name="message"></textarea>
            <input type="submit" value="Send">
        </form>

        <div id="footer">
            <form id="reload" method="POST">
                <input name="token" type="hidden" value="<?php global $md5Pass; echo $md5Pass ?>">
                <?php if (isset($_POST['name'])) echo "<input name=\"name\" type=\"hidden\" value=\"".$_POST['name']."\">";?>
                <p id = "left">This doesn't auto update, you'll have to <input type="submit" value="Reload"></input> to see new messages.</p>
            </form>
            <p id = "right"><a href="VVViewer.php">Click here to return to the schedule.</p>
        </div>

        <script>
            function scrollBottom() {window.scrollTo(0, 99999);}
            if (document.addEventListener) document.addEventListener("DOMContentLoaded", scrollBottom, false)
            else if (window.attachEvent) window.attachEvent("onload", scrollBottom);
            else window.onload=scrollBottom;
        </script>
    </body>
</html>