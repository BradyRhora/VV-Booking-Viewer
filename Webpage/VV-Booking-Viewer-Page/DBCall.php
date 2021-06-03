
<?php
    //ini_set('display_errors', 1);
    //ini_set('display_startup_errors', 1);
    //error_reporting(E_ALL);

    class MyDB extends SQLite3 {
        function __construct() {
        $this->open('../../Databases.db');
        }
    }

    class MemberNote{
        public $Name;
        public $Notes;
        function __construct($name, $notes){
            $this->Name = $name;
            $this->Notes = $notes;
        }
    }

    class Message{
        public $ID;
        public $Name;
        public $Message;
        public $Time;
        function __construct($id,$name,$msg,$time){
            $this->ID=$id;
            $this->Name=$name;
            $this->Message=$msg;
            $this->Time=$time;
        }
    }

    
    //$_POST['Type'] = 'GetNotes';
    if (isset($_POST['Type']))
    {
	$db = new MyDB(); 
	$db->busyTimeout(5000);   
	$type = $_POST['Type'];
        if ($type == 'GetNotes')
        {
            $name = $_POST['Name'];
            //$name = 'Jamie Sherwin';
            $cmd = $db->prepare("SELECT * FROM MemberInfo WHERE Name = :name");
            $cmd->bindValue(":name",$name);
            $result = $cmd->execute();
            $arr = $result->fetchArray(SQLITE3_ASSOC);
            $member = new MemberNote($arr['Name'],$arr['Notes']);
	    echo json_encode($member);
	    $result->finalize();
        } else if ($type == 'SetNotes') {
            $name = $_POST['Name'];
            $notes = $_POST['Notes'];
            
            $cmd = $db->prepare("INSERT OR REPLACE INTO MemberInfo VALUES(:name,:notes)");
            $cmd->bindValue(':name',$name);
            $cmd->bindValue(':notes',$notes);
            $result = $cmd->execute();
            $result->finalize();
        } else if ($type == 'GetMessages') {
            $id = $_POST['ID'];
            $cmd = $db->prepare("SELECT * FROM MESSAGES WHERE ID > :id");
            $cmd->bindValue(':id',$id);
            $res = $cmd->execute();
            $msgs = [];
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                array_push($msgs,new Message($row['ID'],$row['Name'],$row['Message'],$row['DateTime']));
            }
            echo json_encode($msgs);
            $res->finalize();
        } else if ($type == 'SendMessage') {
            $name = $_POST['Name'];
            $message = $_POST['Message'];
            $cmd = $db->prepare("INSERT INTO messages(name,message,datetime) VALUES(:name,:msg,datetime('now'))");
            $cmd->bindValue(':msg', $message);
            $cmd->bindValue(':name', $name);
            $res = $cmd->execute();
            $res->finalize();
	}
	$db->close();
	unset($db);
    }
?>
