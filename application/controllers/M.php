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
	
	public function history($nik){
		$rs=$this->db->select("nik,dt,tmin,tmout,photoin,photoout")->where("nik",$nik)->order_by("dt","DESC")->limit(10)->get("hr_attend")->result();
		header('Content-Type: application/json');
		echo json_encode($rs);
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
					$photo=$this->doupload('photo');
					if($photo!=''){
						$datain=array("dt"=>date('Y-m-d'),"nik"=>$nik,"tmin"=>$tm,"edin"=>$tm,"reasonin"=>$ctt,"latin"=>$lat,"lngin"=>$lng,"photoin"=>$photo,"status"=>"onsite","typ"=>"Masuk");
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
								$msg="Already Out";
							}
						}else{ //no record yet
							$this->db->insert("hr_attend",$datain);
							$msg="In";
							$success=true;
						}
						if($success){
							$msg="Success $msg";
						}
					}else{
						$msg="Photo upload failed";
					}
				}else{
					$msg="Outside office area, please add a note";
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
		header('Content-Type: application/json');
		echo $out;
	}
	
	private function doupload($userfile){
		$config['upload_path']          = './files/';
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
