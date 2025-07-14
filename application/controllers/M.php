<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		// Your own constructor code
		
		// Allow some methods?
		$filtered = array(
			'attend',
			'in'
		);
		if ( in_array($this->router->fetch_method(), $filtered ) )
		{
			$cd=$this->input->post("app_version_code");
			$nm=$this->input->post("app_version");
			$ver = $this->vercheck($cd,$nm);
			if(!$ver[0]){
				$data["ver"]=$ver;
				$this->err($ver[1]);
			}
		}
	}
	
	public function index(){
		echo "";
	}
	public function err($ver)
	{
		$out='{
		  "status": 426,
		  "error": {
			"code": "VERSION_NOT_SUPPORTED",
			"message": "'.$ver["release_notes"].'",
			"latest_version": "'.$ver["version_code"].'",
			"link":"'.$ver["download_url"].'"
		  }
		}';
		header('Content-Type: application/json');
		echo $out;
		exit;
	}
	
	private function getout($success,$msg){
		$out='{
			"error": true,
			"status": 401,
			"msg": '.$msg.'
		  }';
		
		if($success){ 
		  $out='{
			"status": 200,
			"values": '.$msg.
		  '}';
		}
		return ($out);
	}
	
	private function vercheck($code,$ver){
		$ok=false;
		$v=$this->db->get("app_versions")->row_array();
		if(isset($v)){
			if($v["version_code"]==$code && $v["version_name"]==$ver){
				$ok=true;
			}
		}
		return array($ok,$v);
	}
	
	public function in(){
		$success=false; $msg='"Unknown user"';
		$nik=$this->input->post("nip");
		$did=$this->input->post("device_id");
		$pwd=$this->input->post("pwd");
		$usr=$this->db->select("unik,uname,ugrp")->where(array("upwd"=>md5($pwd),"unik"=>$nik))->get("core_user")->row();
		if(is_object($usr)){
			$this->db->update("hr_kary",array("device"=>$did),"nik='$nik'");
			$success=true;
			$msg=json_encode($usr);
		}
		header('Content-Type: application/json');
		echo $this->getout($success,$msg);
	}
	public function whoami(){
		$success=false; $msg='"Unknown device, please login"';
		$device=$this->input->get_request_header('X-device', TRUE);
		$kar=$this->db->where(array("device"=>$device,"device <>"=>""))->get("hr_kary")->row();
		if(is_object($kar)){
			$usr=$this->db->select("unik,uname,ugrp")->where(array("unik <>"=>"","unik"=>$kar->nik))->get("core_user")->row();
			if(is_object($usr)){
				$msg=json_encode($usr);
				$success=true;
			}
		}
		
		header('Content-Type: application/json');
		echo $this->getout($success,$msg);
	}
	public function sup(){
		
		$success=false; $msg='"Unknown referal, please check"';
		$device=$this->input->get_request_header('X-referal', TRUE);
		if($device=='a46e382c34'){
			$uid=$this->input->post('uid');
			$uname=$this->input->post('uname');
			$upwd=$this->input->post('upwd');
			
			$datain=array("uid"=>$uid,"uname"=>$uname,"uloc"=>'IOS',"upwd"=>md5($pwd),"ulvl"=>"11","ugrp"=>'',"uprof"=>'',"uavatar"=>'');
			$this->db->insert("core_user",$datain);
			$msg='Success';
			$success=true;
		}
		
		header('Content-Type: application/json');
		echo $this->getout($success,$msg);
	}
	
	public function notifs($grp){
		header('Content-Type: application/json');
		if($grp!=''){
			$this->db->select("event,m.host,msg,dtm");
			$this->db->from("core_notify m");
			$this->db->join("core_node n","n.host=m.host");
			$rs=$this->db->where("grp",$grp)->order_by("dtm","DESC")->limit(50)->get()->result();
			echo json_encode($rs);
		}else{
			echo json_encode(array());
		}
	}
	public function history($nik){
		$rs=$this->db->select("nik,dt,concat(tmin,' WIB') as tmin,concat(tmout,' WIB') as tmout,photoin,photoout")->where("nik",$nik)->order_by("dt","DESC")->limit(10)->get("hr_attend")->result();
		header('Content-Type: application/json');
		echo json_encode($rs);
	}
	public function story($nik){
		$rs=$this->db->select("nik,txt,photo,dtm")->where("nik",$nik)->order_by("dtm","DESC")->limit(10)->get("hr_story")->result();
		header('Content-Type: application/json');
		echo json_encode($rs);
	}
	public function upstory(){
		date_default_timezone_set("Asia/Jakarta");
		
		$success=false; $msg='"Unknown device, please login"';
		$device=$this->input->get_request_header('X-device', TRUE);
		$nik=$this->input->post("nip");
		$txt=$this->input->post("txt");
		$kar=$this->db->where(array("device"=>$device,"nik"=>$nik))->get("hr_kary")->row();
		if(is_object($kar)){//ketemu
			$photo=$this->douploads('photo','./story/');
			$datain=array("nik"=>$nik,"txt"=>$txt,"photo"=>$photo,"dtm"=>date("Y-m-d H:i:s"));
			$this->db->insert("hr_story",$datain);
			$success=true;
			$msg='"Data saved"';
		}
		header('Content-Type: application/json');
		echo $this->getout($success,$msg);
	}
	public function attend(){
		date_default_timezone_set("Asia/Jakarta");
		
		$msg='"Wrong NIK"';
		$nik=$this->input->post("nip");
		$did=$this->input->post("device_id");
		$lat=$this->input->post("latitude");
		$lng=$this->input->post("longitude");
		$ctt=$this->input->post("reason");
		$tm=date("H:i:s");
		
		$success=false;
		
		$kar=$this->db->where("nik",$nik)->get("hr_kary")->row();
		if(is_object($kar)&&$did!=''){//ketemu
			if($did==$kar->device||$kar->device==''){ //new user or correct device
				if($kar->device==''){//update device id
					$this->db->update("hr_kary",array("device"=>$did),"nik='$nik'");
				}
				$go=true;$stt='onsite';
				if(!$this->geofence($nik,$lat,$lng)){//periksa geofence
					$stt='offsite';
					if($ctt=='') $go=false;
				}
				if($go){
					//do absensi
					$photo=$this->doupload('photo');
					if($photo!=''){
						$datain=array("dt"=>date('Y-m-d'),"nik"=>$nik,"tmin"=>$tm,"edin"=>$tm,"reasonin"=>$ctt,"latin"=>$lat,"lngin"=>$lng,"photoin"=>$photo,"status"=>$stt,"typ"=>"Masuk");
						$dataout=array("tmout"=>$tm,"edout"=>$tm,"reasonout"=>$ctt,"latout"=>$lat,"lngout"=>$lng,"photoout"=>$photo);
						$abs=$this->db->where(array("dt"=>date('Y-m-d'),"nik"=>$nik))->get("hr_attend")->row();
						if(is_object($abs)){// periksa
							if($abs->tmin=='00:00:00'){ //in
								$this->db->update("hr_attend",$datain,"rowid=".$abs->rowid);
								$msg="In";
								$success=true;
							}elseif($abs->tmout=='00:00:00'){ //out
								$this->db->update("hr_attend",$dataout,"rowid=".$abs->rowid);
								$msg="Out";
								$success=true;
							}else{
								$msg='"Already Out"';
							}
						}else{ //no record yet
							$this->db->insert("hr_attend",$datain);
							$msg="In";
							$success=true;
						}
						if($success){
							$msg='"Success '.$msg.'"';
						}
					}else{
						$msg='"Photo upload failed"';
					}
				}else{
					$msg='"Outside office area, please add a note"';
				}
			}else{
				$msg='"Device doesnt match, please ask admin to reset"';
			}
		}
		
		header('Content-Type: application/json');
		echo $this->getout($success,$msg);
	}
	
	private function doupload($userfile,$dir='./files/'){
		$config['upload_path']          = $dir;
		$config['allowed_types']        = 'jpg|png';
		
		$this->load->library('upload', $config);
		
		if ( ! $this->upload->do_upload($userfile))
		{
			$error = array('error' => $this->upload->display_errors());
			return '';
		}
		else
		{
			$data = array('upload_data' => $this->upload->data());
			return $this->upload->data('file_name');
		}
	}
	private function douploads($fld,$dir='./files/'){
		$config['upload_path']          = $dir;
		$config['allowed_types']        = 'jpg|png';
		
		$this->load->library('upload', $config);
		
		$ret=array();
		// Count total files
        $countfiles = count($_FILES[$fld]['name']);
		// Looping all files
        for($i=0;$i<$countfiles;$i++){
			if(!empty($_FILES[$fld]['name'][$i])){
				// Define new $_FILES array - $_FILES['file']
				  $_FILES['file']['name'] = $_FILES[$fld]['name'][$i];
				  $_FILES['file']['type'] = $_FILES[$fld]['type'][$i];
				  $_FILES['file']['tmp_name'] = $_FILES[$fld]['tmp_name'][$i];
				  $_FILES['file']['error'] = $_FILES[$fld]['error'][$i];
				  $_FILES['file']['size'] = $_FILES[$fld]['size'][$i];
				
				if ( $this->upload->do_upload('file')){
						$ret[]= $this->upload->data('file_name');
					}
			}
		}
		
		return implode(";",$ret);
	}
	
	private function geofence($nik,$lat,$lng){
		//default false
		$ret=false;
		//default 100 meter
		$max=100;
		
		//get all loc of this employee
		$usr=$this->db->where("unik",$nik)->get("core_user")->row();
		if(is_object($usr)){// ketemu
			if(trim($usr->uloc)==''){
				$ret=true; //user is national
			}else{
				$locs=explode(",",$usr->uloc);
				//get all locs and the distances
				$geos=$this->db->select("distance_between(lat,lng,'$lat','$lng') as jarak")->where_in("locid",$locs)->get("core_location")->result();
				foreach($geos as $g){
					if(floatval($g->jarak)<=$max) $ret=true;
				}
			}
		}
		return $ret;
	}
}
