<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M extends CI_Controller {

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
	
	public function attend(){
		date_default_timezone_set("Asia/Jakarta");
		
		$msg="Wrong NIK";
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
				$go=true;
				if($ctt==''){
					//periksa geofence
					$go=$this->geofence($nik,$lat,$lng);
				}
				if($go){
					//do absensi
					$datain=array("dt"=>date('Y-m-d'),"nik"=>$nik,"tmin"=>$tm,"edin"=>$tm,"reasonin"=>$ctt,"latin"=>$lat,"lngin"=>$lng,"status"=>"onsite","typ"=>"Masuk");
					$dataout=array("tmout"=>$tm,"edout"=>$tm,"reasonout"=>$ctt,"latout"=>$lat,"lngout"=>$lng);
					$abs=$this->db->where(array("dt"=>date('Y-m-d'),"nik"=>$nik))->get("hr_attend")->row();
					if(is_object($abs)){// periksa
						if($abs->tmin=='00:00:00'){ //in
							$this->db->update("hr_attend",$datain,"rowid=".$abs->rowid);
						}elseif($abs->tmout=='00:00:00'){ //out
							$this->db->update("hr_attend",$dataout,"rowid=".$abs->rowid);
						}else{
							$msg="Already out";
						}
					}else{ //no record yet
						$this->db->insert("hr_attend",$datain);
					}
					if($this->db->affected_rows()>0){
						$msg="Success";
						$success=true;
					}else{
						$msg="No update";
					}
				}else{
					$msg="Outside office area, please make a note";
				}
			}else{
				$msg="Device doesnt match, please ask admin to reset";
			}
		}
		$out="{
			error: true,
			status: 401,
			msg: '$msg'
		  }";
		
		if($success){ 
		  $out="{
			status: 200,
			values: '$msg'
		  }";
		}
		echo $out;
	}
	
	private function geofence($nik,$lat,$lng){
		return true;
	}
}
