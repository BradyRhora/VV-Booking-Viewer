
<?php
    class MyDB extends SQLite3 {
        function __construct() {
        $this->open('../Databases.db');
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

    $db = new MyDB();
    if (isset($_GET['Type']))
    {
        $type = $_GET['Type'];
        if ($type == 'GetNotes')
        {
            $name = $_GET['Name'];

            $cmd = $db->prepare("SELECT * FROM MemberInfo WHERE Name = :name");
            $cmd->bindValue(":name",$name);
            $result = $cmd->execute();
            $arr = $result->fetchArray(SQLITE3_ASSOC);
            $member = new MemberNote($arr['Name'],$arr['Notes']);
            echo json_encode($member);
        } else if ($type == 'SetNotes') {
            $name = $_GET['Name'];
            $notes = $_GET['Notes'];
            
            $cmd = $db->prepare("INSERT OR REPLACE INTO MemberInfo VALUES(:name,:notes)");
            $cmd->bindValue(':name',$name);
            $cmd->bindValue(':notes',$notes);
            $result = $cmd->execute();
        }
    }
?>