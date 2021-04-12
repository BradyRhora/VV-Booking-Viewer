<html>
    <head>
        <?php Header("Cache-Control: max-age=3000, must-revalidate"); ?>

        <title>VV Booking Viewer</title>
        <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

    </head>
    <body>
        <a href="VVViewer.php"><img id="logo" src="resources/logo-variety-ontario.png"></img></a>
        <div id="menu">
            <h1>Welcome to the Variety Village Booking Viewer</h1>
            <p>Please fill in the input to view the schedule:</p>
            <form action="VVViewer.php" method="POST">
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

            <!--<form>
            </form>-->
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
                    //var_dump($stm->getSQL());
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
                            array_push($Bookings,new Booking($row['SECTION'], $slot, ""])); //blank name to keep schedule lined up
                        }
                    }
                }
                $db->close();


                //print_r($Bookings);
                $sections = array_unique(array_map(function($x) { return $x->section;},$Bookings));
                foreach($sections as $section){
                    $slots = array_values(array_filter($Bookings,function($x){global $section; return $x->section==$section;}));
                    echo "<h2>".$section."</h2><table>";
                    echo "<tr>";
                    for ($i = 0; $i < count($slots); $i++){
                        $time = new DateTime($slots[$i]->starttime);
                        echo "<th>".$time->format('g:i a')."</th>";
                    }
                    echo "<tr>";
                    for ($o = 0; $o < max(array_map(function($x) {return count($x->names);}, $slots)); $o++){
                        for ($i = 0; $i < count($slots); $i++){
                            if (count($slots[$i]->names) > $o){
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


            <?php
                //if no time selected display some kind of calender with booking info for the month
                if (!isset($_PUSH['date'])){
                    $date = new Date();
                    $date->setDate($date->getYear(), $date->getMonth(), 1); //wrong
                }
            ?>
        </div>

        <div id="footer">
            <p id = "left">If there's an error, let Brady know!</p>
            
            <p id = "right"><?php 
                $db = new MyDB();
                $com = "SELECT VALUE FROM INFO WHERE NAME = \"LastUpdate\"";
                $ret = $db->querySingle($com);
                
                echo "Database last updated on: " . $ret;
            ?></p>
        </div>
    <?php
        function console_log($output, $with_script_tags = true) {
            $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
            if ($with_script_tags) 
                $js_code = '<script>' . $js_code . '</script>';
            echo $js_code;
        }
    ?>
   
    </body>
</html>
