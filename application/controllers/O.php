<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class O extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		//$this->load->view('welcome_message');
		return "";
	}
	
	public function onoff(){
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$rs=$this->db->select("count(host) as c,sum(status) s")->get("core_status")->row_array();
				$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>array("total"=>$rs["c"],"on"=>$rs["s"],"off"=>$rs["c"]-$rs["s"]));
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		
		echo json_encode($retval);
	}
	
	public function map(){
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$sel="lat,lng,concat(l.name,'\n',l.addr) as name,locid,sum(s.status) as onoff,count(n.host) as cnt, (count(n.host)-sum(s.status)) as off";
				$this->db->join("core_node n","l.locid=n.loc");
				$this->db->join("core_status s","n.host=s.host");
				$wh=array("lat<>"=>"","lng<>"=>"");
				$grpb =array("lat","lng","l.name","l.addr","locid");
				$rs=$this->db->select($sel)->where($wh)->group_by($grpb)->get("core_location l")->result();
				$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>$rs);
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		
		echo json_encode($retval);
	}
	
	public function down(){
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$sel="n.host, name, MY_SECTOTIME(TIMESTAMPDIFF(SECOND,downsince,now())) as mstt";
				$this->db->join("core_status s","n.host=s.host");
				$wh=array("status"=>"0","downsince is not NULL"=>null);
				$orde="downsince";
				$rs=$this->db->select($sel)->where($wh)->order_by($orde)->get("core_node n")->result();
				$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>$rs);
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		
		echo json_encode($retval);
	}
	
	public function net(){
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$sel="dari,ke";
				//$this->db->join("core_status s","n.host=s.host");
				//$wh=array("status"=>"0","downsince is not NULL"=>null);
				//$orde="downsince";
				$rs=$this->db->select($sel)->get("core_netdiagram")->result();
				$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>$rs);
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		
		echo json_encode($retval);
	}
}
