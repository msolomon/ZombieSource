<?php
class Player_model extends CI_Model{
    private $table_name = 'player';
    // private $table_fields = array(
    //     'id' => 'uuid',
    //     'gameid' => 'uuid',
    //     'name' => 'string',
    //     'datecreated' => 'datetime',
    // );
    private $table_editable_fields = array(
        'original_zombie' => 'int',
        'dateremoved' => 'datetime'
    );

    function __construct(){
        parent::__construct();
    }

    public function getPlayerIDByHumanCodeGameID($human_code, $gameid){
        $this->db->select('playerid');
        $this->db->from('player_data');
        $this->db->join('player', 'player_data.playerid = player.id');
        $this->db->join('game', 'player.gameid = game.id');
        $this->db->where('game.id',$gameid);
        $this->db->where('player_data.name','human_code');
        $this->db->where('player_data.value',$human_code);
        $query = $this->db->get();
        if($query->num_rows() != 1){
            throw new InvalidHumanCodeException('Did not find a playerid for human_code '.$human_code.' and gameid'.$gameid);
        }
        
        return $query->row()->playerid;
    }
    
    public function playerExistsByPlayerID($playerid){
        $this->db->select('id');
        $this->db->from($this->table_name);
        $this->db->where('id',$playerid);
        $query = $this->db->get();
        if($query->num_rows() != 1){
            throw new PlayerDoesNotExistException('Did not find a player for playerid '.$playerid);
        }
        
        return $query->row()->id;
    }

    public function getPlayerID($userid, $gameid){
        $this->db->select('id');
        $this->db->from($this->table_name);
        $this->db->where('userid',$userid);
        $this->db->where('gameid',$gameid);
        $query = $this->db->get();
        if($query->num_rows() != 1){
            throw new PlayerDoesNotExistException('Did not find a playerid for userid '.$userid.' and gameid'.$gameid);
        }
        
        return $query->row()->id;
    }

    // @TODO: restrict active to not banned... probably need a column in the db for that
    public function getActivePlayerIDsByGameID($gameid){
        $this->db->select('id');
        $this->db->from($this->table_name);
        $this->db->where('gameid',$gameid);
        $query = $this->db->get();
        
        $playeridArray = array();
        foreach($query->result() as $row){
            $playeridArray[] = $row->id;
        }
        return $playeridArray;
    }

    private function getPlayerTableData($playerid, $name){
        $this->db->select($name);
        $this->db->from($this->table_name);
        $this->db->where('id',$playerid);
        $query = $this->db->get();

        if($query->num_rows() != 1){
            throw new DatastoreException('More (or less) results for playerid than expected, results: '.$query->num_rows());
        }

        return $query->row()->$name;
    }

    private function getPlayerDataTableData($playerid, $name){
        $query = $this->db->query('SELECT value FROM player_data WHERE playerid = '.$this->db->escape($playerid).' AND name = '.$this->db->escape($name).' ORDER BY timestamp DESC LIMIT 1');
        $result = $query->row();
        $value = null;
        if(isset($result->{'value'})){
            $value = $result->{'value'};
        }
        
        return $value;
    }

    public function getPlayerData($playerid, $name){
        if(array_key_exists($name, $this->table_editable_fields)){
            return $this->getPlayerTableData($playerid, $name);
        } else {
            return $this->getPlayerDataTableData($playerid, $name);
        }
    }

    private function setPlayerDataTableData($playerid, $name, $value){
        //date created
        $datecreated = gmdate("Y-m-d H:i:s", time());

        //insert new player data
        $data = array(
            'playerid' => $playerid,
            'name' => $name,
            'timestamp' => $datecreated,
            'value' => $value
        );
        $this->db->insert('player_data',$data);
        $added = true;
        return $added;
    }

    private function setPlayerTableData($playerid, $name, $value){
        $data = array(
            $name => $value
        );
        $this->db->where('id',$playerid);
        $this->db->update($this->table_name,$data);
        $added = true;
        return $added;
    }

    public function setPlayerData($playerid, $name, $value){
        if(array_key_exists($name, $this->table_editable_fields)){
            return $this->setPlayerTableData($playerid, $name, $value);
        } else {
            return $this->setPlayerDataTableData($playerid, $name, $value);
        }
    }

    public function createPlayerInGame($userid, $gameid){
        $added = false;
            
        //date created
        $datecreated = gmdate("Y-m-d H:i:s", time());
            
        //get new UUID
        $query = $this->db->query('SELECT UUID() as "uuid"');
        $uuid = $query->row()->{'uuid'};
            
        //insert new player
        $data = array(
            'id' => $uuid,
            'userid' => $userid,
            'gameid' => $gameid,
            'datecreated' => $datecreated,
            'original_zombie' => NULL
        );
        $this->db->insert('player',$data);
                
        return $uuid;
    }
    
    public function getNumberOfPlayersInGame($gameid){
        $query = $this->db->query('SELECT COUNT(id) as count FROM player WHERE gameid = '.$this->db->escape($gameid));
        $result = $query->row();
        $count = "";
        if(isset($result->{'count'})){
            $count = $result->{'count'};
        }
        
        return $count;
    }
    
    // both name and value must match exactly(upper/lower)
    public function getNumberOfPlayersInGameByNVP($gameid,$name,$value){
        $query = $this->db->query('SELECT COUNT(*) as count FROM player_data 
                                    LEFT JOIN (player) ON (player.id = player_data.playerid) 
                                    WHERE player_data.name = '.$this->db->escape($name).' 
                                    AND player_data.value = '.$this->db->escape($value).' 
                                    AND player.gameid = '.$this->db->escape($gameid));
        $result = $query->row();
        $count = "";
        if(isset($result->{'count'})){
            $count = $result->{'count'};
        }
        
        return $count;
    }
        
    // @TODO: write isActiveHuman
    public function isActiveHuman($playerid){
        
    }
    
    // @TODO: write isActiveZombie
    public function isActiveZombie($playerid){
        return true;
    }
}
?>