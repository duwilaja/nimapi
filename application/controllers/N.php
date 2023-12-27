<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class N extends CI_Controller {

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
		$today=date("Y-m-d");
		$r=array();
		$where=array();
		$grpby="";
		$join=array();
		switch($x){
			case "app": $grpby="";
						$join=array(array("nmsdb.applications a","a.device_id=d.device_id"));
						
						$r=array("nmsdb.devices d",
						"hostname,sysName,sysDescr,app_type,app_state",$where,$grpby); 
						break;
			case "cat": $grpby="typ";
						$join=array(array("core_status s","n.host=s.host","left"));
						
						$r=array("core_node n",
						"typ,count(s.host) as t, sum(s.status) as u, count(s.host)-sum(s.status) as d,date_format(min(checked),'%a %e %b, %H:%i') as cek",$where,$grpby); 
						break;
			case "loc": $grpby="locid,l.name,l.addr,city,prov";
						$join=array(array("core_node n","l.locid=n.loc","left"),array("core_status s","n.host=s.host","left"));
						
						$r=array("core_location l",
						"locid,l.name,l.addr,city,prov,count(s.host) as t, sum(s.status) as u, count(s.host)-sum(s.status) as d",$where,$grpby); 
						break;
			case "dvc": $params=$this->input->post(array("status"));
						if($params["status"]!="") $where=array("status"=>$params["status"]);
						$join=array(array("core_status s","n.host=s.host","left"),array("core_location l","n.loc=l.locid","left"));
						$r=array("core_node n",
						"n.host,n.name,net,loc,addr,city,prov,grp,typ,if(status=1,'UP','DOWN') as stt,checked,svc,bw,lan,wan,sid,if(status=1,'',downsince) as downtime,n.rowid",$where,$grpby); 
						break;
		}
		$r[]=$join;
		return $r;
	}
	private function olah($d){
		for($i=0;$i<count($d);$i++){
			$dd=$d[$i];
			$dd->x = $dd->t==0?0:round(($dd->u/$dd->t*100),2);
			$d[$i]=$dd;
		}
		return $d;
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
				if(count($asql)>1){
					if(count($asql[2])>0) $this->db->where($asql[2]);
					if($asql[3]!="") $this->db->group_by($asql[3]);
					if(count($asql[4])>0){
						foreach($asql[4] as $join){
							$this->db->join($join[0],$join[1],$join[2]);
						}
					}
					$data=$this->db->select($asql[1])->from($asql[0])->get()->result();
					if($menu=="sla"){
						$data=$this->olah($data);
					}
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
	
}
