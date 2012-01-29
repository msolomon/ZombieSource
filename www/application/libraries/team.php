<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Team extends CI_Controller {
  private $teamid;

  public function __construct($teamid = null)
  {
      parent::__construct();
      $this->load->model('Team_model', '', true);
      $this->load->model('Player_team_model', '', true);

  }

  public static function getTeamByTeamID($teamid){
      if($teamid != null){
          $instance = new self();
          $instance->teamid = $teamid;
          return $instance;
      } else {
          throw new Exception("Teamid cannot be null.");
      }
  }

  public static function getNewTeam($name, $playerid){
          $instance = new self();
          $instance->teamid = $instance->Team_model->createTeam($name, GAME_KEY);
          $instance->Player_team_model->addPlayerToTeam($instance->teamid, $playerid);
          return $instance;
  }

  public function getDataArray(){
      $data = array();
      $data['name'] = $this->getData('name');
      $data['description'] = $this->getData('name');
      $data['profile_pic_url'] = $this->getGravatarHTML();
      $data['gravatar_email'] = $this->getData('gravatar_email');
      return $data;
  }

  public function getTeamID(){
    return $this->teamid;
  }

  public function getData($key){
    return $this->Team_model->getTeamData($this->teamid, $key);
  }

  public function setData($key, $value){
    $this->Team_model->setTeamData($this->teamid, $key, $value);
  }

  public function getGravatarHTML($size = 250){
    $gravatar_email = $this->getData('gravatar_email');
    $team_name = $this->getData('name');
    if($gravatar_email && $gravatar_email != ''){
      return $this->build_gravatar($gravatar_email, $size, 'identicon', 'x', true);
    }
    else{
      return $this->build_gravatar(md5($team_name), $size, 'identicon', 'x', true);
    }
  }

   public function build_gravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
      $url = 'http://www.gravatar.com/avatar/';
      $url .= md5( strtolower( trim( $email ) ) );
      $url .= "?s=$s&d=$d&r=$r";
      if ( $img ) {
          $url = '<img src="' . $url . '"';
          foreach ( $atts as $key => $val )
              $url .= ' ' . $key . '="' . $val . '"';
          $url .= ' />';
      }
      return $url;
  }

}