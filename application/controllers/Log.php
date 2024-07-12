<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Log extends CI_Controller {

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
		echo "";
	}
	
	public function in(){
		$retval=array("code"=>"404","ttl"=>"Failed","msgs"=>"Wrong User/Password");
		$usr=trim($this->input->post("usr"));
		$pwd=trim($this->input->post("pwd"));
		$this->db->where('uid',$usr);
		$this->db->where('upwd',md5($pwd));
		$acc=$this->db->get("core_user")->result_array();
		if(count($acc)>0){
			$token=md5(uniqid(rand(), true)).md5(uniqid(rand(), true));
			$this->session->set_userdata('user_token',$token);
			$this->session->set_userdata('user_data',$acc);
			$retval=array("code"=>"200","ttl"=>"OK","msgs"=>$token);
		}
		
		echo json_encode($retval);
	}
	public function out()
	{
		session_destroy();
		$retval=array("code"=>"200","ttl"=>"OK","msgs"=>"Logged out");
		echo json_encode($retval);
	}
	
	public function whoami(){
		$retval=array('code'=>"403",'ttl'=>"Session closed",'msgs'=>"Please login");
		$user=$this->session->userdata('user_token');
		$auth=$this->input->get_request_header('X-token', TRUE);
		if(isset($user)){
			if($auth==$user){
				$retval=array("code"=>"200","ttl"=>"OK","msgs"=>$this->session->userdata("user_data"));
			}else{
				$retval=array('code'=>"403",'ttl'=>"Failed",'msgs'=>"Invalid Auth");
			}
		}
		echo json_encode($retval);
	}
}
