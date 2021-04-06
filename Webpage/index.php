<html>
    <head>
        <title>VV Booking Viewer</title>
        <link rel="stylesheet" href="style.css">

        <style>
            
            table, th,td {
                border: 1px solid black;
                border-collapse: collapse;
                font-size:20px;
                color:#f1faee;
            }
            td{
                background-color:#457b9d;
                width:120px;
            }
            th{
                background-color:#1d3557;
            }
            td.empty{
                background-color:#e63946;
            }
        
        </style>
    </head>
    <body>
        <h1>Welcome to the Variety Village Booking Viewer</h1>
        <p>Please fill in the input to view the schedule:</p>
        <form action="index.php">
            <label for="area">Choose an area:</label>
            <select name="area" id="area">
                <option value="Aquatics" <?php if (isset($_GET["area"]) && $_GET["area"] == "Aquatics") echo "selected"?>>Aquatics</option>
                <option value="Fieldhouse" <?php if (isset($_GET["area"]) && $_GET["area"] == "Fieldhouse") echo "selected"?>>Fieldhouse</option>
                <option value="Cardioroom" <?php if (isset($_GET["area"]) && $_GET["area"] == "Cardioroom") echo "selected"?>>Cardio Room</option>
                <option value="Weightroom" <?php if (isset($_GET["area"]) && $_GET["area"] == "Weightroom") echo "selected"?>>Weight Room</option>
            </select>
            <br><br>
            <label for="date">Choose a date:</label>
            <input type="date" id="date" name="date" <?php if (isset($_GET["date"])) echo "value=\"".$_GET["date"]."\"" ?>>
            <br><br>
            <input type="submit" id="submit" name="submit" >
        </form>


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

         if(isset($_GET["area"]))
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
            $stm1->bindValue(':area',$_GET["area"]);
            $stm1->bindValue(':date',$_GET["date"]);
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
                $stm->bindValue(':area',$_GET["area"]);
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
                        array_push($Bookings,new Booking($row['SECTION'], $slot, []));
                    }
                }
                $ret->finalize();
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
                    echo "<th>".$time->format('H:i')."</th>";
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
    </body>
</html>