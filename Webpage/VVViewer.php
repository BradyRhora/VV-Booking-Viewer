<html>
    <head>
        <?php Header("Cache-Control: max-age=3000, must-revalidate"); ?>

        <title>VV Booking Viewer</title>
        <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#b91d47">
        <meta name="theme-color" content="#ffffff">
    </head>
    <body>
        <a href="VVViewer.php"><img id="logo" src="resources/logo-variety-ontario.png"></img></a>
        <div id="menu">
            <h1>Welcome to the Variety Village Booking Viewer</h1>
                    <form id="schedForm" action="VVViewer.php" method="POST">
                        <label for="area">Choose an area:</label>
                        <select name="area" id="area">
                            <option value="Aquatics" <?php if (isset($_POST["area"]) && $_POST["area"] == "Aquatics") echo "selected"?>>Aquatics</option>
                            <option value="Fieldhouse" <?php if (isset($_POST["area"]) && $_POST["area"] == "Fieldhouse") echo "selected"?>>Fieldhouse</option>
                            <option value="Cardio Room" <?php if (isset($_POST["area"]) && $_POST["area"] == "Cardio Room") echo "selected"?>>Cardio Room</option>
                            <option value="Weight Room" <?php if (isset($_POST["area"]) && $_POST["area"] == "Weight Room") echo "selected"?>>Weight Room</option>
                        </select>
                        <br><br>
                        <label for="date">Choose a date:</label>
                        <input type="date" id="date" name="date" <?php if (isset($_POST["date"])) echo "value=\"".$_POST["date"]."\"" ?>>
                        <button onclick="prevDay()"><</button>
                        <button onclick="setTime(new Date())">Today</button>
                        <button onclick="nextDay()">></button>
                        <br><br>
                        <input type="submit" id="submit" name="submit" >
                    </form>
                    <form id="searchForm" action="VVViewer.php" method="POST">
                        <label for="search">Member lookup:</label>
                        <input id="search" name="search" <?php if (isset($_POST["search"])) echo "value=\"".$_POST["search"]."\""?> onfocus="this.value = this.value;"></input>
                        <?php if (isset($_POST["search"])) echo "<script>document.getElementById(\"search\").focus()</script>" ?>
                        <input type="submit" name="submit" value="Search">
                    </form>
            </table>
        </div>
        <script>
            function setTime(date){
                var year = date.getFullYear().toString();
                var month = (date.getMonth()+1).toString();
                var day = date.getDate().toString();
                console.log(year + " " + month + " " + day);
                if (month.length == 1) month = "0"+month;
                if (day.length == 1)day = "0"+day;
                document.getElementsByTagName("input")[0].setAttribute("value",year+"-"+month+"-"+day);
            }

            function nextDay(){
                var currentDay = new Date(document.getElementsByTagName("input")[0].getAttribute("value") + " EST");
                console.log(currentDay);
                currentDay.setDate(currentDay.getDate()+1);
                console.log(currentDay);
                setTime(currentDay);
            }

            function prevDay(){
                var currentDay = new Date(document.getElementsByTagName("input")[0].getAttribute("value") + " EST");
                currentDay.setDate(currentDay.getDate()-1);
                setTime(currentDay);
            }

        </script>

        <div id="schedules">
            <?php
            class MyDB extends SQLite3 {
                function __construct() {
                $this->open('../Databases.db');
                }
            }

            class Booking {
                public $names;
                public $section;
                public $starttime;
                function __construct( $section, $starttime, $names) {
                    $this->names=$names;
                    $this->section=$section;
                    $this->starttime=$starttime;
                }

                function addname($name){
                    array_push($this->names,$name);
                }
            }

            if(isset($_POST["area"]))
            {

                $db = new MyDB();
                if(!$db) {
                    echo $db->lastErrorMsg();
                }
                $stm1 = $db->prepare(<<<EOT
                SELECT DISTINCT DATETIME
                FROM BOOKINGS
                WHERE strftime('%Y-%m-%d',DATETIME) = strftime('%Y-%m-%d',:date) 
                AND AREA = :area
                ORDER BY DATETIME
            EOT);
                $stm1->bindValue(':area',$_POST["area"]);
                $stm1->bindValue(':date',$_POST["date"]);
                $ret1 = $stm1->execute();
                $slots = [];
                while ($row1 = $ret1->fetchArray(SQLITE3_ASSOC)) {
                    array_push($slots, $row1['DATETIME']);
                }
                $ret1->finalize();
                
                $Bookings = [];
                foreach($slots as $slot)
                {
                    $stm = $db->prepare(<<<EOT
                    SELECT NAME,SECTION FROM BOOKINGS 
                    WHERE DATETIME = :date
                    AND AREA = :area
            EOT);
                    $stm->bindValue(':area',$_POST["area"]);
                    $stm->bindValue(':date',$slot);
                    $ret = $stm->execute();
                    while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
                        $sec = $row['SECTION'];
                        $potent = array_values(array_filter($Bookings,function($x){
                            global $sec;
                            global $slot;
                            return $x->section == $sec && $x->starttime == $slot;
                        }));
                        
                        if (count($potent) > 0){
                            $potent[0]->addname($row['NAME']);
                        }
                        else {
                            array_push($Bookings,new Booking($row['SECTION'], $slot, [$row['NAME']]));
                        }
                    }
                    $ret->finalize();
                    
                    $secCmd = $db->prepare("SELECT DISTINCT SECTION FROM BOOKINGS WHERE AREA = :area");
                    $secCmd->bindValue(":area",$_POST["area"]);
                    $secRet = $secCmd->execute();

                    $sections = [];
                    while ($secRow = $secRet->fetchArray(SQLITE3_ASSOC)) {
                        array_push($sections, $secRow['SECTION']);
                    }
                    $secRet->finalize();

                    foreach($sections as $section){
                        $potent2 = array_values(array_filter($Bookings,function($x){
                            global $section;
                            global $slot;
                            return $x->section == $section && $x->starttime == $slot;
                        }));
                        

                        if (count($potent2) == 0){
                            array_push($Bookings,new Booking($section, $slot, [""])); //blank name to keep schedule lined up
                        }
                    }
                    
                }
                $db->close();


                $sections = array_unique(array_map(function($x) { return $x->section;},$Bookings));
                foreach($sections as $section){
                    $slots = array_values(array_filter($Bookings,function($x){global $section; return $x->section==$section;}));
                    echo "<h2>".$section."</h2><table id=\"daySched\">";
                    echo "<tr>";
                    for ($i = 0; $i < count($slots); $i++){
                        $time = new DateTime($slots[$i]->starttime);
                        echo "<th>".$time->format('g:i a')."</th>";
                    }
                    echo "<tr>";
                    for ($o = 0; $o < max(array_map(function($x) {return count($x->names);}, $slots)); $o++){
                        for ($i = 0; $i < count($slots); $i++){
                            if (count($slots[$i]->names) > $o && $slots[$i]->names[0]!=""){
                                echo "<td>".$slots[$i]->names[$o]."</td>";
                            } else {
                                echo "<td class=\"empty\"></td>";
                            }
                        }
                        echo "<tr>";
                    }
                    echo "</table>";
                }
                
            }
            ?>

        </div>

        <div id="memberSchedule">
            <?php
                if (isset($_POST["search"])){
                    $name = $_POST["search"];
                    $db = new MyDB();
                    if(!$db) {
                        echo $db->lastErrorMsg();
                    }
                    $stm = $db->prepare(<<<EOT
                    SELECT * FROM BOOKINGS
                    WHERE NAME LIKE :name
                    ORDER BY DATETIME
                EOT);
                    $stm->bindValue(':name','%'.$name.'%');
                    $ret = $stm->execute();
                    echo "<table><tr><th>Name</th><th>Area</th><th>Section</th><th>Time</th></tr>";
                    
                    while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
                        echo "<tr>";
                        echo "<td>".$row["NAME"]."</td>";
                        echo "<td>".$row["AREA"]."</td>";
                        echo "<td>".$row["SECTION"]."</td>";
                        echo "<td>".$row["DATETIME"]."</td>";
                        echo "</tr>";
                    }
                    $ret->finalize();
                    
                    echo "</table>";

                    /*
                    $stm = $db->prepare("SELECT * FROM BOOKINGS WHERE NAME LIKE '%:name%' ORDER BY DATETIME");
                    $stm->bindValue(':name',$name);
                    var_dump($stm->getSQL(true));
                    $ret = $stm->execute();
                    */
                }
            ?>

        </div>

        <div id="footer">
            <p id = "left"><?php 
                $msgs = ["Remember, if Brady isn't at work then this probably isn't up to date!",
                        "Hope your shift is going well!",
                        "If you're reading this... 😎 You're pretty cool 😎"];
                echo $msgs[rand(0,count($msgs)-1)];
            ?></p>
            
            <p id = "right"><?php 
                $db = new MyDB();
                $com = "SELECT VALUE FROM INFO WHERE NAME = \"LastUpdate\"";
                $ret = $db->querySingle($com);
                
                echo "Database last updated on: " . $ret;
                
            ?>

            <svg onclick="togglechat()" id="chatbutton" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <path d="M2.001 9.352c0 1.873.849 2.943 1.683 3.943.031 1 .085 1.668-.333 3.183 1.748-.558 2.038-.778 3.008-1.374 1 .244 1.474.381 2.611.491-.094.708-.081 1.275.055 2.023-.752-.06-1.528-.178-2.33-.374-1.397.857-4.481 1.725-6.649 2.115.811-1.595 1.708-3.785 1.661-5.312-1.09-1.305-1.705-2.984-1.705-4.695-.001-4.826 4.718-8.352 9.999-8.352 5.237 0 9.977 3.484 9.998 8.318-.644-.175-1.322-.277-2.021-.314-.229-3.34-3.713-6.004-7.977-6.004-4.411 0-8 2.85-8 6.352zm20.883 10.169c-.029 1.001.558 2.435 1.088 3.479-1.419-.258-3.438-.824-4.352-1.385-.772.188-1.514.274-2.213.274-3.865 0-6.498-2.643-6.498-5.442 0-3.174 3.11-5.467 6.546-5.467 3.457 0 6.546 2.309 6.546 5.467 0 1.12-.403 2.221-1.117 3.074zm-7.563-3.021c0-.453-.368-.82-.82-.82s-.82.367-.82.82.368.82.82.82.82-.367.82-.82zm3 0c0-.453-.368-.82-.82-.82s-.82.367-.82.82.368.82.82.82.82-.367.82-.82zm3 0c0-.453-.368-.82-.82-.82s-.82.367-.82.82.368.82.82.82.82-.367.82-.82z"/>
            </svg>
            </p>
        </div>
    <?php
        function console_log($output, $with_script_tags = true) {
            $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
            if ($with_script_tags) 
                $js_code = '<script>' . $js_code . '</script>';
            echo $js_code;
        }
    ?>
    
    <div id="chat">
        <p>Enter iPad password:</p>
        <form id="submitPass" action="chat.php" method="POST">
            <input type="password" readonly id="pwbox" name="pass"></input>
            <table>
                <tr> <td onclick="addnum(7)">7</td> <td onclick="addnum(8)">8</td> <td onclick="addnum(9)">9</td> </tr>
                <tr> <td onclick="addnum(4)">4</td> <td onclick="addnum(5)">5</td> <td onclick="addnum(6)">6</td> </tr>
                <tr> <td onclick="addnum(1)">1</td> <td onclick="addnum(2)">2</td> <td onclick="addnum(3)">3</td> </tr>
                <tr> <td style="background-color:#5db765"><input type="submit" value="🙂"></td> <td onclick="addnum(0)">0</td> <td onclick="remnum()" style="background-color:#B75D69">🙁</td> </tr>
            </table>
        </form>
    </div>
    
    <script>
        var open = false;
        function togglechat(){
            var chat = document.getElementById("chat");
            if (open)
                chat.style.display = "none";
            else
                chat.style.display = "block";
            open = !open;
        }

        var pw = "";
        function addnum(num){
            if (pw.length < 4) {
                pw += num;
                var box = document.getElementById("pwbox");
                box.value = pw;
            }
        }

        function remnum(){
            pw = pw.substring(0,pw.length-1);
            
            var box = document.getElementById("pwbox");
            box.value = "*".repeat(pw.length);
        }
    </script>
    

    </body>
</html>
