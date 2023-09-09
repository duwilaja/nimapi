<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class R extends CI_Controller {

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
			//case "usr": $r=array("core_user","*"); break;
			case "stt": $params=$this->input->post(array("from","to")); //updown
						if($params["from"]=="") $params["from"]=$today;
						if($params["to"]=="") $params["to"]=$today;
						$where=array("dtm>="=>$params["from"]." 00:00:00","dtm<="=>$params["to"]." 23:59:59");
						$join=array(array("core_node n","n.host=l.host","left"));
						
						$r=array("core_log l","l.host,n.name,l.dtm,if(status=1,'UP','DOWN') as stts",$where,$grpby); 
						break;
			case "sla": $params=$this->input->post(array("from","to"));
						if($params["from"]=="") $params["from"]=$today;
						if($params["to"]=="") $params["to"]=$today;
						$where=array("dt>="=>$params["from"],"dt<="=>$params["to"]);
						$grpby="n.host,n.name,n.net,n.typ,x";
						$join=array(array("core_status_sla s","n.host=s.host","left"));
						if($params['from']==$today) $join[0]=array("core_status s","n.host=s.host","left");
						
						$r=array("core_node n",
						"n.host,n.name,n.net,n.typ,sec_to_time(sum(uptime)) as ut,sec_to_time(sum(downtime)) as d,'0' as x,sum(uptime) as u,sum(downtime+uptime) as t",$where,$grpby); 
						break;
			case "loc": $grpby="locid,l.name,l.addr";
						$join=array(array("core_node n","l.locid=n.loc","left"),array("core_status s","n.host=s.host","left"));
						
						$r=array("core_location l",
						"locid,l.name,l.addr,count(s.host) as t, sum(s.status) as u, count(s.host)-sum(s.status) as d",$where,$grpby); 
						break;
			case "dvc": $r=array("core_node","host,name,net,loc,grp,typ,sla,snmpenabled,svc,bw,lan,wan,sid",$where,$grpby); break;
			case "prf": $params=$this->input->post(array("from","to"));
						if($params["from"]=="") $params["from"]=$today;
						if($params["to"]=="") $params["to"]=$today;
						$where=array("dt>="=>$params["from"],"dt<="=>$params["to"]);
						$grpby="n.host,n.name,n.net,n.typ";
						$join=array(array("core_status_sla s","n.host=s.host","left"));
						if($params['from']==$today) $join[0]=array("core_status s","n.host=s.host","left");
						
						$r=array("core_node n",
						"n.host,n.name,n.net,n.typ,round(avg(rtt),2) as art, round(avg(ifnull(lost/cnt,0)*100),2) as lst",$where,$grpby); 
						break;
			case "svr": $id=$this->input->post("rowid");
						if($id=="") $id="0";
						$svr=$this->db->where("rowid",$id)->get("core_severity")->row_array();
						$txt=""; $fld="''";
						if($svr){
							$fld=$svr["sensor"]; $txt=$svr["severity"];
							$min=$svr["mn"]; $max=$svr["mx"]; $net=$svr["net"];
							$where=array("$fld >="=>$min,"$fld <="=>$max);
							if($net!="") $where=array("$fld >="=>$min,"$fld <="=>$max,"net="=>$net);
						}else{
							$where=array("0="=>1);
						}
						$join=array(array("core_status s","n.host=s.host","left"));
						$r=array("core_node n","n.host,n.name,n.net,n.typ,$fld as val,'$txt' as txt",$where,$grpby);
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
				$params=$this->input->post(array("from","to"));
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
