<html>
    <head>
        <?php Header("Cache-Control: max-age=3000, must-revalidate"); ?>
        <title>Super Secret Staff Chat</title>
        <link rel="stylesheet" href="/VV-Booking-Viewer-Page/chatstyle.css?v=<?php echo time(); ?>">
        <link rel="apple-touch-icon" sizes="180x180" href="/VV-Booking-Viewer-Page/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/VV-Booking-Viewer-Page/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/VV-Booking-Viewer-Page/favicon-16x16.png">
        <link rel="manifest" href="/VV-Booking-Viewer-Page/site.webmanifest">
        <link rel="mask-icon" href="/VV-Booking-Viewer-Page/safari-pinned-tab.svg" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#b91d47">
        <meta name="theme-color" content="#ffffff">
    </head>
    <body>
        <?php
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
            class MyDB extends SQLite3 {
                function __construct() {
                $this->open('../../Databases.db');
                }
            }
            $shaPass = "";
            if (isset($_POST["pass"])){
                $pass = $_POST["pass"];
                $shaPass = hash('sha256',$pass);
            } else if (isset($_POST["token"])){
                $shaPass = $_POST["token"];
            }
            if ($shaPass != "") 
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
                    echo "<input name=\"token\" type=\"hidden\" value=\"".$shaPass."\">";
                    echo "</form></div></div>";
                }
                
                $db = new MyDB();
                $cmd = $db->prepare("SELECT * FROM INFO WHERE NAME='ChatPass' AND VALUE = :pass");
                $cmd->bindValue(':pass',$shaPass);
                $res = $cmd->execute();
                $ret = $res->fetchArray();
                if ($ret)
                {
                    $ret = $db->query("SELECT * FROM MESSAGES ORDER BY DATETIME");
                        
                    echo "<div id=\"messages\">";
                    while ($row = $ret->fetchArray(SQLITE3_ASSOC))
                    {
                        echo "<div id=\"msg\" data-id=\"".$row['ID']."\">";
                        echo "<div id=\"msgHeader\">";
                        echo "<b class=\"name\">".$row['Name']."</b> ";
                        echo "<p class=\"time\" class=\"time\">".$row['DateTime']."</p></div>";
                        echo "<p class=\"message\">".$row['Message']."</p> ";
                        echo "</div>";
                    }
                }
                else 
                {
                    echo "<p style=\"color:black;\">Password invalid. Click <a href=\"/VV-Booking-Viewer-Page/VVViewer.php\">here</a> to return to the main page.</p>";
                }
            } 
            else 
            {
                echo "<p style=\"color:black;\">Password invalid. Click <a href=\"/VV-Booking-Viewer-Page/VVViewer.php\">here</a> to return to the main page.</p>";
            }
            ?>
        </div>
        <form id="textBox" onsubmit="return sendMessage()">
            <input name="token" type="hidden" value="<?php global $shaPass; echo $shaPass ?>">
            <input name="name" type="hidden" value="<?php if (isset($_POST['name'])) echo $_POST['name'] ?>">
            <textarea id="message" name="message"></textarea>
            <input type="submit" value="Send">
        </form>

        <div id="footer">
            <p id = "left">Hey whaddup this the super secret chat</p>
            <p id = "right"><a href="/VV-Booking-Viewer-Page/VVViewer.php">Click here to return to the schedule.</p>
        </div>

        <script>
            function scrollBottom() {window.scrollTo(0, 99999);}
            if (document.addEventListener) document.addEventListener("DOMContentLoaded", scrollBottom, false)
            else if (window.attachEvent) window.attachEvent("onload", scrollBottom);
            else window.onload=scrollBottom;

            function sendMessage(){
                //debugger;
                var data = new URLSearchParams();
                var formData = new FormData(document.querySelector('form'));
                var name = formData.get('name');
                data.append("Name",name);
                var txtBox = document.getElementById("message");
                
                data.append("Message",txtBox.value);
                data.append("Type","SendMessage");
                fetch('DBCall.php', {
                    method: 'post',
                    body: data
                });
                txtBox.value="";
                return false;
            }

            function updateChat(){
                var msgDiv = document.getElementById('messages');
                var last = msgDiv.children[msgDiv.children.length-1];
                var id = last.getAttribute('data-id');

                var data = new URLSearchParams();
                data.append('Type',"GetMessages");
                data.append('ID',id);
                

                var data = fetch('/VV-Booking-Viewer-Page/DBCall.php',{method:'post',body:data})
                        .then(response=>response.json())
                        .then(value=>{
                            value.forEach(function(e){
                                var d = document.createElement("div");
                                msgDiv.appendChild(d);
                                d.id = "msg";
                                d.setAttribute('data-id',e['ID']);
                                var head = document.createElement("div");
                                head.id = "msgHeader";
                                d.appendChild(head);
                                var name = document.createElement("b");
                                name.className = "name";
                                name.innerText = e['Name'];
                                head.appendChild(name);
                                var time = document.createElement("p");
                                time.className = "time";
                                time.innerText = e['Time'];
                                head.appendChild(time);
                                var msg = document.createElement("p");
                                msg.className="message";
                                msg.innerText=e['Message'];
                                d.appendChild(msg);

                                });
                    });

                setTimeout(updateChat,1500);
            }

            setTimeout(updateChat,1500);
        </script>
    </body>
</html>
