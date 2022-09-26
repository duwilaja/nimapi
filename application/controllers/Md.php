<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Md extends CI_Controller {

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
	private function sql($x)
	{
		$r=array();
		switch($x){
			//case "usr": $r=array("core_user","*"); break;
			case "svr": $r=array("core_severity","sensor,name,net,mn,mx,severity"); break;
			case "sla": $r=array("core_sla","sla,sd,ed,st,et"); break;
			case "loc": $r=array("core_location","locid,name,addr,city,prov,postal,area,lat,lng"); break;
			case "dvc": $r=array("core_node","host,name,loc,grp,typ,net,snmp,snmp_community,snmp_ver,sla"); break;
			case "net": $r=array("core_netdiagram","dari,ke"); break;
		}
		return $r;
	}
	public function ls()
	{
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$menu=$this->input->post("menu");
				$asql=$this->sql($menu);
				if(count($asql)>0){
					$data=$this->db->select("rowid,".$asql[1])->get($asql[0])->result();
					$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>$data);
				}else{
					$retval=array('code'=>"404",'ttl'=>"Invalid menu",'msgs'=>"Invalid menu '$menu'");
				}
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		echo json_encode($retval);
	}
	public function dt()
	{
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$menu=$this->input->post("menu");
				$rowid=$this->input->post("rowid");
				$asql=$this->sql($menu);
				if(count($asql)>0){
					$data=$this->db->select($asql[1])->where("rowid",$rowid)->get($asql[0])->result();
					$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>$data);
				}else{
					$retval=array('code'=>"404",'ttl'=>"Invalid menu",'msgs'=>"Invalid menu '$menu'");
				}
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		echo json_encode($retval);
	}
	public function ins(){
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$menu=$this->input->post("menu");
				//$rowid=$this->input->post("rowid");
				//$flag=$this->input->post("flag");
				
				$asql=$this->sql($menu);
				if(count($asql)>0){
					//if($flag=='DELETE'){
					//	$this->db->delete($asql[0],"rowid=".$rowid);
					//}else{
						$data=$this->input->post(explode(",",$asql[1]));
					//	if($rowid=="0"||$flag=="INSERT"){
							$this->db->insert($asql[0],$data);
					//	}else{
					//		$this->db->update($asql[0],$data,"rowid=".$rowid);
					//	}
					//}
					$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>$this->db->affected_rows()." rows inserted");
				}else{
					$retval=array('code'=>"404",'ttl'=>"Invalid menu",'msgs'=>"Invalid menu '$menu'");
				}
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		echo json_encode($retval);
	}
	public function upd(){
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$menu=$this->input->post("menu");
				$rowid=$this->input->post("rowid");
				//$flag=$this->input->post("flag");
				
				$asql=$this->sql($menu);
				if(count($asql)>0){
					//if($flag=='DELETE'){
					//	$this->db->delete($asql[0],"rowid=".$rowid);
					//}else{
						$data=$this->input->post(explode(",",$asql[1]));
					//	if($rowid=="0"||$flag=="INSERT"){
					//		$this->db->insert($asql[0],$data);
					//	}else{
							$this->db->update($asql[0],$data,"rowid=".$rowid);
					//	}
					//}
					$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>$this->db->affected_rows()." rows updated");
				}else{
					$retval=array('code'=>"404",'ttl'=>"Invalid menu",'msgs'=>"Invalid menu '$menu'");
				}
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		echo json_encode($retval);
	}
	public function del(){
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		$data=array();
		if(isset($user)){
			if($auth==$user){
				$menu=$this->input->post("menu");
				$rowid=$this->input->post("rowid");
				//$flag=$this->input->post("flag");
				
				$asql=$this->sql($menu);
				if(count($asql)>0){
					//if($flag=='DELETE'){
						$this->db->delete($asql[0],"rowid=".$rowid);
					//}else{
					//	$data=$this->input->post(explode(",",$asql[1]));
					//	if($rowid=="0"||$flag=="INSERT"){
					//		$this->db->insert($asql[0],$data);
					//	}else{
					//		$this->db->update($asql[0],$data,"rowid=".$rowid);
					//	}
					//}
					$retval=array('code'=>"200",'ttl'=>"OK",'msgs'=>$this->db->affected_rows()." row deleted");
				}else{
					$retval=array('code'=>"404",'ttl'=>"Invalid menu",'msgs'=>"Invalid menu '$menu'");
				}
			}else{
				$retval=array('code'=>"403",'ttl'=>"Invalid session",'msgs'=>"Invalid token");
			}
		}
		echo json_encode($retval);
	}
}
