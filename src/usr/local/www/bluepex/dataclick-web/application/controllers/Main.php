<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(0);

class Main extends CI_Controller {

    private $response;
    public $utm;
    public $status;
    public $roles;

    function __construct(){
        parent::__construct();
        $this->load->model('User_model', 'user_model', TRUE);
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->status = $this->config->item('status');
        $this->roles = $this->config->item('roles');
        $this->load->library('userlevel');

        checkIfUtmDefaultExists();

        checkPermission("dataclick-web/reports", true);

        $this->load->helper("reports");
        $this->load->library('form_validation');
        $this->load->model("UtmModel");

        checkPermission("dataclick-web/utm", true);

        $this->load->library('form_validation');
        $this->load->model("UtmModel");

        $utm_id = $this->appsession->getSessionKey('utm_display');
        if (!empty($utm_id)) {
            $this->utm = $this->UtmModel->find($utm_id);
        } else {
            $this->utm = $this->UtmModel->getUtmDefault();
        }
        $conn = [
            "protocol" => $this->utm->protocol,
            "host" => $this->utm->host,
            "port" => $this->utm->port,
            "user" => $this->utm->username,
            "pass" => $this->utm->password,
        ];
        $this->load->library("DataClickApi/DataClickApi", $conn);

    }

    //index
	public function index()
	{
	    //user data from session
	    $data = $this->session->userdata;
	    if(empty($data)){
	        redirect(site_url().'main/login/');
	    }
        // check token expired
        $is_expired = $this->user_model->isTokenExpired($data['id']);
        //print_r($is_expired);die;
        if($is_expired == "expired"){
            redirect('main/logout');
        }
	    //check user level
	    if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }
	    $dataLevel = $this->userlevel->checkLevel($data['role']);
	    //check user level
        
	    $data['title'] = "DataClick 2.0";
	    
        if(empty($this->session->userdata['email'])){
            redirect(site_url().'main/login/');
        }else{
            //$this->load->view('header', $data);
            //$this->load->view('navbar', $data);
            //$this->load->view('container');
            //$this->load->view('index', $data);
            //$this->load->view('footer');
            $this->simpletemplate->render("index", $data);

        }

	}

    // dashboard
    public function dashboard($period = "daily", $blocked = false)
    {
        checkPermission("dataclick-web/dashboard");

        //user data from session
        $data = $this->session->userdata;
        if(empty($data)){
            redirect(site_url().'main/login/');
        }
        // check token expired
        $is_expired = $this->user_model->isTokenExpired($data['id']);
        //print_r($is_expired);die;
        if($is_expired == "expired"){
            redirect('main/logout');
        }
        //check user level
        if(empty($data['role'])){
            redirect(site_url().'main/login/');
        }

        $result = $this->dataclickapi->getDashboardData($period, $blocked);
        $data = [
            "utm" => $this->utm,
            "utms" => $this->UtmModel->findAll(),
            "period" => $period,
            "json" => $this->dataclickapi->getDashboardData($period, $blocked),
        ];
        $this->simpletemplate->render("dashboard", $data);
    }

    // reports
    public function reports()
    {

        //user data from session
        $data = $this->session->userdata;
        if(empty($data)){
            redirect(site_url().'main/login/');
        }
        // check token expired
        $is_expired = $this->user_model->isTokenExpired($data['id']);
        //print_r($is_expired);die;
        if($is_expired == "expired"){
            redirect('main/logout');
        }
        //check user level
        if(empty($data['role'])){
            redirect(site_url().'main/login/');
        }

        $data = [
            "reports" => getReports(),
            "periods" => getPeriods(),
            "users" => $this->dataclickapi->getUsersFromDatabase(),
            "users_config" => $this->dataclickapi->getUsersFromConfigXML(),
            "groups" => $this->dataclickapi->getGroupsFromDatabase(),
        ];

        $this->simpletemplate->render("reports", $data);
    }

    public function utm()
    {

        //user data from session
        $data = $this->session->userdata;
        if(empty($data)){
            redirect(site_url().'main/login/');
        }
        // check token expired
        $is_expired = $this->user_model->isTokenExpired($data['id']);
        //print_r($is_expired);die;
        if($is_expired == "expired"){
            redirect('main/logout');
        }
        //check user level
        if(empty($data['role'])){
            redirect(site_url().'main/login/');
        }

        $utms = $this->UtmModel->findAll();
        $this->simpletemplate->render("utm/list", ["utms" => $utms]);
    }
	
	public function checkLoginUser(){
	     //user data from session
	    $data = $this->session->userdata;
	    if(empty($data)){
	        redirect(site_url().'main/login/');
	    }
	    
	$this->load->library('user_agent');
        $browser = $this->agent->browser();
        $os = $this->agent->platform();
        $getip = $this->input->ip_address();
        
        $result = $this->user_model->getAllSettings();
        $stLe = $result->site_title;
	$tz = $result->timezone;
	    
	$now = new DateTime();
        $now->setTimezone(new DateTimezone($tz));
        $dTod =  $now->format('Y-m-d');
        $dTim =  $now->format('H:i:s');
        
        $this->load->helper('cookie');
        $keyid = rand(1,9000);
        $scSh = sha1($keyid);
        $neMSC = md5($data['email']);
        $setLogin = array(
            'name'   => $neMSC,
            'value'  => $scSh,
            'expire' => strtotime("+2 year"),
        );
        $getAccess = get_cookie($neMSC);
	    
        if(!$getAccess && $setLogin["name"] == $neMSC){
            $this->load->library('email');
            $this->load->library('sendmail');
            $bUrl = base_url();
            $message = $this->sendmail->secureMail($data['first_name'],$data['last_name'],$data['email'],$dTod,$dTim,$stLe,$browser,$os,$getip,$bUrl);
            $to_email = $data['email'];
            $this->email->from($this->config->item('register'), 'New sign-in! from '.$browser.'');
            $this->email->to($to_email);
            $this->email->subject('New sign-in! from '.$browser.'');
            $this->email->message($message);
            $this->email->set_mailtype("html");
            $this->email->send();
            
            $this->input->set_cookie($setLogin, TRUE);
            redirect('main/dashboard');
        }else{
            $this->input->set_cookie($setLogin, TRUE);
            redirect('main/logout');
        }
	}
	
	public function settings(){

	    $data = $this->session->userdata;
        if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }
	    $dataLevel = $this->userlevel->checkLevel($data['role']);
	    //check user level

        $data['title'] = "Settings";
        $this->form_validation->set_rules('site_title', 'Site Title', 'required');
        $this->form_validation->set_rules('timezone', 'Timezone', 'required');
        $this->form_validation->set_rules('recaptcha', 'Recaptcha', 'required');
        $this->form_validation->set_rules('theme', 'Theme', 'required');

        $result = $this->user_model->getAllSettings();
        $data['id'] = $result->id;
	    $data['site_title'] = $result->site_title;
	    $data['timezone'] = $result->timezone;
        
	    if (!empty($data['timezone']))
	    {
	        $data['timezonevalue'] = $result->timezone;
	        $data['timezone'] = $result->timezone;
	    }
	    else
	    {
	        $data['timezonevalue'] = "";
            $data['timezone'] = $this->lang->line('utm_select_time_zone');
	    }
	    
	    if($dataLevel == "is_admin"){
            if ($this->form_validation->run() == FALSE) {
                //$this->load->view('header', $data);
                //$this->load->view('navbar', $data);
                //$this->load->view('container');
                //$this->load->view('settings', $data);
                //$this->load->view('footer');
                $this->simpletemplate->render("settings", $data);
            }else{
                $post = $this->input->post(NULL, TRUE);
                $cleanPost = $this->security->xss_clean($post);
                $cleanPost['id'] = $this->input->post('id');
                $cleanPost['site_title'] = $this->input->post('site_title');
                $cleanPost['timezone'] = $this->input->post('timezone');
                $cleanPost['recaptcha'] = $this->input->post('recaptcha');
                $cleanPost['theme'] = $this->input->post('theme');
    
                if(!$this->user_model->settings($cleanPost)){
                    $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_your_data_error'));
                }else{
                    $this->session->set_flashdata('success_message', $this->lang->line('utm_update_your_data_success'));
                }
                redirect(site_url().'main/settings/');
            }
	    }else{
            redirect(site_url().'main/');
        }
	}
    
    //user list
	public function users()
	{
	    $data = $this->session->userdata;
	    $data['title'] = "User List";
	    $data['groups'] = $this->user_model->getUserData();

	    //check user level
	    if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }
	    $dataLevel = $this->userlevel->checkLevel($data['role']);
	    //check user level

	    //check is admin or not
	    if($dataLevel == "is_admin"){
            //$this->load->view('header', $data);
            //$this->load->view('navbar', $data);
            //$this->load->view('container');
            //$this->load->view('user', $data);
            //$this->load->view('footer');
            $this->simpletemplate->render("user", $data);

	    }else{
	        redirect(site_url().'main/');
	    }
	}

    	//change level user
	public function changelevel()
	{
        $data = $this->session->userdata;
        //check user level
	    if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }
	    $dataLevel = $this->userlevel->checkLevel($data['role']);
	    //check user level

	    $data['title'] = $this->lang->line('utm_update_title_change_level');
	    $data['groups'] = $this->user_model->getUserData();

	    //check is admin or not
	    if($dataLevel == "is_admin"){

            $this->form_validation->set_rules('email', $this->lang->line('utm_select_your_email'), 'required');
            $this->form_validation->set_rules('level', $this->lang->line('utm_select_user_level'), 'required');

            if ($this->form_validation->run() == FALSE) {
                //$this->load->view('header', $data);
                //$this->load->view('navbar', $data);
                //$this->load->view('container');
                //$this->load->view('changelevel', $data);
                //$this->load->view('footer');
                $this->simpletemplate->render("changelevel", $data);
            }else{
                $cleanPost['email'] = $this->input->post('email');
                $cleanPost['level'] = $this->input->post('level');
                if(!$this->user_model->updateUserLevel($cleanPost)){
                    $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_your_level_error'));
                }else{
                    $this->session->set_flashdata('success_message', $this->lang->line('utm_update_your_level_success'));
                }
                redirect(site_url().'main/changelevel');
            }
	    }else{
	        redirect(site_url().'main/');
	    }
	}
    
    	//ban or unban user
	public function banuser() 
	{
        $data = $this->session->userdata;
        //check user level
	    if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }
	    $dataLevel = $this->userlevel->checkLevel($data['role']);
	    //check user level

	    $data['title'] = "Ban User";
	    $data['groups'] = $this->user_model->getUserData();

	    //check is admin or not
	    if($dataLevel == "is_admin"){

            $this->form_validation->set_rules('email', $this->lang->line('utm_select_your_email'), 'required');
            $this->form_validation->set_rules('banuser',  $this->lang->line('utm_update_ban_user_unban'), 'required');
            if ($this->form_validation->run() == FALSE) {
                //$this->load->view('header', $data);
                //$this->load->view('navbar', $data);
                //$this->load->view('container');
                //$this->load->view('banuser', $data);
                //$this->load->view('footer');
                $this->simpletemplate->render("banuser", $data);
            }else{
                $post = $this->input->post(NULL, TRUE);
                $cleanPost = $this->security->xss_clean($post);
                $cleanPost['email'] = $this->input->post('email');
                $cleanPost['banuser'] = $this->input->post('banuser');
                if(!$this->user_model->updateUserban($cleanPost)){
                    $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_general_msg_error'));
                }else{
                    $this->session->set_flashdata('success_message', $this->lang->line('utm_update_general_msg_success'));
                }
                redirect(site_url().'main/banuser');
            }
	    }else{
	        redirect(site_url().'main/');
	    }
	}

    //edit user
	public function changeuser() 
    {
        $data = $this->session->userdata;
        if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }

        $dataInfo = array(
            'firstName'=> $data['first_name'],
            'id'=>$data['id'],
        );

        $data['title'] = "Change Password";
        $this->form_validation->set_rules('firstname', $this->lang->line('utm_user_first_name'), 'required');
        $this->form_validation->set_rules('lastname', $this->lang->line('utm_user_last_name'), 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password', $this->lang->line('utm_user_pass'), 'required|min_length[5]');
        $this->form_validation->set_rules('passconf', $this->lang->line('utm_user_confirm_pass'), 'required|matches[password]');

        $data['groups'] = $this->user_model->getUserInfo($dataInfo['id']);

        if ($this->form_validation->run() == FALSE) {
            //$this->load->view('header', $data);
            //$this->load->view('navbar', $data);
            //$this->load->view('container');
            //$this->load->view('changeuser', $data);
            //$this->load->view('footer');
            $this->simpletemplate->render("changeuser", $data);
        }else{
            $this->load->library('password');
            $post = $this->input->post(NULL, TRUE);
            $cleanPost = $this->security->xss_clean($post);
            $hashed = $this->password->create_hash($cleanPost['password']);
            $cleanPost['password'] = $hashed;
            $cleanPost['user_id'] = $dataInfo['id'];
            $cleanPost['email'] = $this->input->post('email');
            $cleanPost['firstname'] = $this->input->post('firstname');
            $cleanPost['lastname'] = $this->input->post('lastname');
            unset($cleanPost['passconf']);
            if(!$this->user_model->updateProfile($cleanPost)){
                $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_your_profile_error'));
            }else{
                $this->session->set_flashdata('success_message', $this->lang->line('utm_update_your_profile_success'));
            }
            redirect(site_url().'main/');
        }
    }

    //open profile and gravatar user
    public function profile()
    {
        $data = $this->session->userdata;
        if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }

        $data['title'] = "Profile";
        //$this->load->view('header', $data);
        //$this->load->view('navbar', $data);
        //$this->load->view('container');
        //$this->load->view('profile', $data);
        //$this->load->view('footer');
        $this->simpletemplate->render("profile", $data);
    }

    //delete user
    public function deleteuser($id) {
            $data = $this->session->userdata;
            if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }
	    $dataLevel = $this->userlevel->checkLevel($data['role']);
	    //check user level

	    //check is admin or not
	    if($dataLevel == "is_admin"){
    		$this->user_model->deleteUser($id);
    		if($this->user_model->deleteUser($id) == FALSE )
    		{
    		    $this->session->set_flashdata('flash_message', $this->lang->line('utm_delete_user_error'));
    		}
    		else
    		{
    		    $this->session->set_flashdata('success_message', $this->lang->line('utm_delete_user_success'));
    		}
    		redirect(site_url().'main/users/');
	    }else{
		    redirect(site_url().'main/');
	    }
    }

    //add new user from backend
    public function adduser()
    {
        $data = $this->session->userdata;
        if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }

        //check user level
	    if(empty($data['role'])){
	        redirect(site_url().'main/login/');
	    }
	    $dataLevel = $this->userlevel->checkLevel($data['role']);
	    //check user level

	    //check is admin or not
	    if($dataLevel == "is_admin"){
            $this->form_validation->set_rules('firstname', $this->lang->line('utm_user_first_name'), 'required');
            $this->form_validation->set_rules('lastname', $this->lang->line('utm_user_last_name'), 'required');
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            $this->form_validation->set_rules('role', $this->lang->line('utm_users_report_role'), 'required');
            $this->form_validation->set_rules('password', $this->lang->line('utm_user_pass'), 'required|min_length[5]');
            $this->form_validation->set_rules('passconf', $this->lang->line('utm_user_confirm_pass'), 'required|matches[password]');

            $data['title'] = "Add User";
            if ($this->form_validation->run() == FALSE) {
                //$this->load->view('header', $data);
                //$this->load->view('navbar');
                //$this->load->view('container');
                //$this->load->view('adduser', $data);
                //$this->load->view('footer');
                $this->simpletemplate->render("adduser", $data);
            }else{
                if($this->user_model->isDuplicate($this->input->post('email'))){
                    $this->session->set_flashdata('flash_message', 'User email already exists');
                    redirect(site_url().'main/adduser');
                }else{
                    $this->load->library('password');
                    $post = $this->input->post(NULL, TRUE);
                    $cleanPost = $this->security->xss_clean($post);
                    $hashed = $this->password->create_hash($cleanPost['password']);
                    $cleanPost['email'] = $this->input->post('email');
                    $cleanPost['role'] = $this->input->post('role');
                    $cleanPost['firstname'] = $this->input->post('firstname');
                    $cleanPost['lastname'] = $this->input->post('lastname');
                    $cleanPost['banned_users'] = 'unban';
                    $cleanPost['password'] = $hashed;
                    unset($cleanPost['passconf']);

                    //insert to database
                    if(!$this->user_model->addUser($cleanPost)){
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_add_user_error'));
                    }else{
                        $this->session->set_flashdata('success_message', $this->lang->line('utm_update_add_user_success'));
                    }
                    redirect(site_url().'main/users/');
                };
            }
	    }else{
	        redirect(site_url().'main/');
	    }
    }

    //register new user from frontend
    public function register()
    {
        $data['title'] = $this->lang->line('utm_title_register_admin');
        $this->load->library('curl');
        $this->load->library('recaptcha');
        $this->form_validation->set_rules('firstname', $this->lang->line('utm_user_first_name'), 'required');
        $this->form_validation->set_rules('lastname', $this->lang->line('utm_user_last_name'), 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        
        $result = $this->user_model->getAllSettings();
        $sTl = $result->site_title;
        $data['recaptcha'] = $result->recaptcha;

        if ($this->form_validation->run() == FALSE) {
            //$this->load->view('header', $data);
            //$this->load->view('container');
            //$this->load->view('register');
            //$this->load->view('footer');
            $this->simpletemplate->render("register", $data);

        }else{
            if($this->user_model->isDuplicate($this->input->post('email'))){
                $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_add_email_error'));
                redirect(site_url().'main/register');
            }else{
                $post = $this->input->post(NULL, TRUE);
                $clean = $this->security->xss_clean($post);

                if($data['recaptcha'] == 'yes'){
                    //recaptcha
                    $recaptchaResponse = $this->input->post('g-recaptcha-response');
                    $userIp = $_SERVER['REMOTE_ADDR'];
                    $key = $this->recaptcha->secret;
                    $url = "https://www.google.com/recaptcha/api/siteverify?secret=".$key."&response=".$recaptchaResponse."&remoteip=".$userIp; //link
                    $response = $this->curl->simple_get($url);
                    $status= json_decode($response, true);
    
                    //recaptcha check
                    if($status['success']){
                        //insert to database
                        $id = $this->user_model->insertUser($clean);
                        $token = $this->user_model->insertToken($id);
    
                        //generate token
                        $qstring = $this->base64url_encode($token);
                        $url = site_url() . 'main/complete/token/' . $qstring;
                        $link = '<a href="' . $url . '">' . $url . '</a>';
    
                        $this->load->library('email');
                        $this->load->library('sendmail');
                        
                        $message = $this->sendmail->sendRegister($this->input->post('lastname'),$this->input->post('email'),$link, $sTl);
                        $to_email = $this->input->post('email');
                        $this->email->from($this->config->item('register'), 'Set Password ' . $this->input->post('firstname') .' '. $this->input->post('lastname')); //from sender, title email
                        $this->email->to($to_email);
                        $this->email->subject('Set Password Login');
                        $this->email->message($message);
                        $this->email->set_mailtype("html");
    
                        //Sending mail
                        if($this->email->send()){
                            redirect(site_url().'main/successregister/');
                        }else{
                            $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_email_error'));
                            exit;
                        }
                    }else{
                        //recaptcha failed
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_google_recaptcha_error'));
                        redirect(site_url().'main/register/');
                        exit;
                    }
                }else{
                    //insert to database
                    $id = $this->user_model->insertUser($clean);
                    $token = $this->user_model->insertToken($id);
    
                    //generate token
                    $qstring = $this->base64url_encode($token);
                    $url = site_url() . 'main/complete/token/' . $qstring;
                    $link = '<a href="' . $url . '">' . $url . '</a>';
    
                    $this->load->library('email');
                    $this->load->library('sendmail');
                    
                    $message = $this->sendmail->sendRegister($this->input->post('lastname'), $this->input->post('email'),$link,$sTl);
                    $to_email = $this->input->post('email');
                    $this->email->from($this->config->item('register'), 'Set Password ' . $this->input->post('firstname') .' '. $this->input->post('lastname')); //from sender, title email
                    $this->email->to($to_email);
                    $this->email->subject('Set Password Login');
                    $this->email->message($message);
                    $this->email->set_mailtype("html");
    
                    //Sending mail
                    if($this->email->send()){
                        redirect(site_url().'main/successregister/');
                    }else{
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_your_sendding_email'));
                        exit;
                    }
                }
            };
        }
    }

    //if success new user register
    public function successregister()
    {
        $data['title'] = $this->lang->line('utm_update_your_success_register');
        //$this->load->view('header', $data);
        //$this->load->view('container');
        //$this->load->view('register-info');
        //$this->load->view('footer');
        $this->simpletemplate->render("register-info", $data);
    }

    //if success after set password
    public function successresetpassword()
    {
        $data['title'] = $this->lang->line('utm_update_your_success_register');
        //$this->load->view('header', $data);
        //$this->load->view('container');
        //$this->load->view('reset-pass-info');
        //$this->load->view('footer');
        $this->simpletemplate->render("reset-pass-info", $data);
    }

    protected function _islocal(){
        return strpos($_SERVER['HTTP_HOST'], 'local');
    }

    //check if complate after add new user
    public function complete()
    {
        $token = base64_decode($this->uri->segment(4));
        $cleanToken = $this->security->xss_clean($token);

        $user_info = $this->user_model->isTokenValid($cleanToken); //either false or array();

        if(!$user_info){
            $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_your_tokin_expired'));
            redirect(site_url().'main/login');
        }
        $data = array(
            'firstName'=> $user_info->first_name,
            'email'=>$user_info->email,
            'user_id'=>$user_info->id,
            'token'=>$this->base64url_encode($token)
        );

        $data['title'] = $this->lang->line('utm_update_set_password');

        $this->form_validation->set_rules('password', $this->lang->line('utm_user_pass'), 'required|min_length[5]');
        $this->form_validation->set_rules('passconf', $this->lang->line('utm_user_confirm_pass'), 'required|matches[password]');

        if ($this->form_validation->run() == FALSE) {
            //$this->load->view('header', $data);
            //$this->load->view('container');
            //$this->load->view('complete', $data);
            //$this->load->view('footer');
            $this->simpletemplate->render("complete", $data);

        }else{
            $this->load->library('password');
            $post = $this->input->post(NULL, TRUE);

            $cleanPost = $this->security->xss_clean($post);

            $hashed = $this->password->create_hash($cleanPost['password']);
            $cleanPost['password'] = $hashed;
            unset($cleanPost['passconf']);
            $userInfo = $this->user_model->updateUserInfo($cleanPost);

            if(!$userInfo){
                $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_your_problem_record'));
                redirect(site_url().'main/login');
            }

            unset($userInfo->password);

            foreach($userInfo as $key=>$val){
                $this->session->set_userdata($key, $val);
            }
            redirect(site_url().'main/');

        }
    }

    //check login failed or success
    public function login()
    {
        $data = $this->session->userdata;
        if(!empty($data['email'])){
	        redirect(site_url().'main/');
	    }else{
	        $this->load->library('curl');
            $this->load->library('recaptcha');
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            $this->form_validation->set_rules('password', $this->lang->line('utm_user_pass'), 'required');
            
            $data['title'] = $this->lang->line('utm_welcome');
            
            $result = $this->user_model->getAllSettings();
            $data['recaptcha'] = $result->recaptcha;

            if($this->form_validation->run() == FALSE) {
                //$this->load->view('header', $data);
                //$this->load->view('container');
                //$this->load->view('login');
                //$this->load->view('footer');
                $data["disabled_header"] = 'true';
                $this->simpletemplate->render("login", $data);

            }else{
                $post = $this->input->post();
                $clean = $this->security->xss_clean($post);
                $userInfo = $this->user_model->checkLogin($clean);
                
                if ($data['recaptcha'] == 'yes'){
                    //recaptcha
                    $recaptchaResponse = $this->input->post('g-recaptcha-response');
                    $userIp = $_SERVER['REMOTE_ADDR'];
                    $key = $this->recaptcha->secret;
                    $url = "https://www.google.com/recaptcha/api/siteverify?secret=".$key."&response=".$recaptchaResponse."&remoteip=".$userIp; //link
                    $response = $this->curl->simple_get($url);
                    $status= json_decode($response, true);
    
                    if(!$userInfo)
                    {
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_wrong_email_pass'));
                        redirect(site_url().'main/login');
                    }
                    elseif($userInfo->banned_users == "ban")
                    {
                        $this->session->set_flashdata('danger_message', $this->lang->line('utm_tempory_banned_email'));
                        redirect(site_url().'main/login');
                    }
                    else if(!$status['success'])
                    {
                        //recaptcha failed
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_google_recaptcha_error'));
                        redirect(site_url().'main/login/');
                        exit;
                    }
                    elseif($status['success'] && $userInfo && $userInfo->banned_users == "unban") //recaptcha check, success login, ban or unban
                    {
                        foreach($userInfo as $key=>$val){
                        $this->session->set_userdata($key, $val);
                        }
                        redirect(site_url().'main/dashboard');
                    }
                    else
                    {
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_something_error'));
                        redirect(site_url().'main/login/');
                        exit;
                    }
                }else{
                    //print_r($userInfo);die;
                    if(!$userInfo)
                    {
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_wrong_email_pass'));
                        redirect(site_url().'main/login');
                    }
                    elseif($userInfo->banned_users == "ban")
                    {
                        $this->session->set_flashdata('danger_message', $this->lang->line('utm_tempory_banned_email'));
                        redirect(site_url().'main/login');
                    }
                    elseif($userInfo && $userInfo->banned_users == "unban") //recaptcha check, success login, ban or unban
                    {
                        foreach($userInfo as $key=>$val){
                        $this->session->set_userdata($key, $val);
                        }
                        //generate token
                        $token = $this->user_model->insertToken($userInfo->id);
                        $qstring = $this->base64url_encode($token);
                        $url = site_url() . 'main/reset_password/token/' . $qstring;
                        $link = '<a href="' . $url . '">' . $url . '</a>';
                        redirect(site_url().'main/dashboard');
                    }
                    else
                    {
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_something_error'));
                        redirect(site_url().'main/login/');
                        exit;
                    }
                }
            }
	    }
    }

    //Logout
    public function logout()
    {
        $this->session->sess_destroy();
        redirect(site_url().'main/login/');
    }

    //forgot password
    public function forgot()
    {
        $data['title'] = "Forgot Password";
        $this->load->library('curl');
        $this->load->library('recaptcha');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        
        $result = $this->user_model->getAllSettings();
        $sTl = $result->site_title;
        $data['recaptcha'] = $result->recaptcha;

        if($this->form_validation->run() == FALSE) {
            //$this->load->view('header', $data);
            //$this->load->view('container');
            //$this->load->view('forgot');
            //$this->load->view('footer');
            $this->simpletemplate->render("forgot", $data);

        }else{
            $email = $this->input->post('email');
            $clean = $this->security->xss_clean($email);
            $userInfo = $this->user_model->getUserInfoByEmail($clean);

            if(!$userInfo){
                $this->session->set_flashdata('flash_message', $this->lang->line('utm_find_your_email'));
                redirect(site_url().'main/login');
            }

            if($userInfo->status != $this->status[1]){ //if status is not approved
                $this->session->set_flashdata('flash_message', $this->lang->line('utm_account_not_approved_status'));
                redirect(site_url().'main/login');
            }

            if($data['recaptcha'] == 'yes'){
                //recaptcha
                $recaptchaResponse = $this->input->post('g-recaptcha-response');
                $userIp = $_SERVER['REMOTE_ADDR'];
                $key = $this->recaptcha->secret;
                $url = "https://www.google.com/recaptcha/api/siteverify?secret=".$key."&response=".$recaptchaResponse."&remoteip=".$userIp; //link
                $response = $this->curl->simple_get($url);
                $status= json_decode($response, true);
    
                //recaptcha check
                if($status['success']){
    
                    //generate token
                    $token = $this->user_model->insertToken($userInfo->id);
                    $qstring = $this->base64url_encode($token);
                    $url = site_url() . 'main/reset_password/token/' . $qstring;
                    $link = '<a href="' . $url . '">' . $url . '</a>';
    
                    $this->load->library('email');
                    $this->load->library('sendmail');
                    
                    $message = $this->sendmail->sendForgot($this->input->post('lastname'),$this->input->post('email'),$link,$sTl);
                    $to_email = $this->input->post('email');
                    $this->email->from($this->config->item('forgot'), $this->lang->line('utm_reset_password') . '! ' . $this->input->post('firstname') .' '. $this->input->post('lastname')); //from sender, title email
                    $this->email->to($to_email);
                    $this->email->subject($this->lang->line('utm_reset_password'));
                    $this->email->message($message);
                    $this->email->set_mailtype("html");
    
                    if($this->email->send()){
                        redirect(site_url().'main/successresetpassword/');
                    }else{
                        $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_email_error'));
                        exit;
                    }
                }else{
                    //recaptcha failed
                    $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_google_recaptcha_error'));
                    redirect(site_url().'main/register/');
                    exit;
                }
            }else{
                //generate token
                $token = $this->user_model->insertToken($userInfo->id);
                $qstring = $this->base64url_encode($token);
                $url = site_url() . 'main/reset_password/token/' . $qstring;
                $link = '<a href="' . $url . '">' . $url . '</a>';

                $this->load->library('email');
                $this->load->library('sendmail');
                
                $message = $this->sendmail->sendForgot($this->input->post('lastname'),$this->input->post('email'),$link,$sTl);
                $to_email = $this->input->post('email');
                $this->email->from($this->config->item('forgot'), $this->lang->line('utm_reset_password') . '! ' . $this->input->post('firstname') .' '. $this->input->post('lastname')); //from sender, title email
                $this->email->to($to_email);
                $this->email->subject($this->lang->line('utm_reset_password'));
                
                $this->email->message($message);
                $this->email->set_mailtype("html");

                if($this->email->send()){
                    redirect(site_url().'main/successresetpassword/');
                }else{
                    $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_your_sendding_email'));
                    exit;
                }
            }
            
        }

    }

    //reset password
    public function reset_password()
    {
        $token = $this->base64url_decode($this->uri->segment(4));
        $cleanToken = $this->security->xss_clean($token);
        $user_info = $this->user_model->isTokenValid($cleanToken); //either false or array();

        if(!$user_info){
            $this->session->set_flashdata('flash_message', 'Token is invalid or expired');
            redirect(site_url().'main/login');
        }
        $data = array(
            'firstName'=> $user_info->first_name,
            'email'=>$user_info->email,
            //'user_id'=>$user_info->id,
            'token'=>$this->base64url_encode($token)
        );

        $data['title'] = $this->lang->line('utm_reset_password');
        $this->form_validation->set_rules('password', $this->lang->line('utm_user_pass'), 'required|min_length[5]');
        $this->form_validation->set_rules('passconf', $this->lang->line('utm_user_confirm_pass'), 'required|matches[password]');

        if ($this->form_validation->run() == FALSE) {
            //this->load->view('header', $data);
            //this->load->view('container');
            //this->load->view('reset_password', $data);
            //this->load->view('footer');
            $this->simpletemplate->render("reset_password", $data);

        }else{
            $this->load->library('password');
            $post = $this->input->post(NULL, TRUE);
            $cleanPost = $this->security->xss_clean($post);
            $hashed = $this->password->create_hash($cleanPost['password']);
            $cleanPost['password'] = $hashed;
            $cleanPost['user_id'] = $user_info->id;
            unset($cleanPost['passconf']);
            if(!$this->user_model->updatePassword($cleanPost)){
                $this->session->set_flashdata('flash_message', $this->lang->line('utm_update_your_password_error'));
            }else{
                $this->session->set_flashdata('success_message', $this->lang->line('utm_update_your_password_success'));
            }
            redirect(site_url().'main/checkLoginUser');
        }
    }

    public function base64url_encode($data) {
      return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function base64url_decode($data) {
      return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    public function refreshData()
    {
        checkPermission("dataclick-web/dashboard");
        $result = $this->dataclickapi->refreshData();
        if (isset($result->status) && $result->status == "ok") {
            $this->response['ok'] = $this->lang->line('utm_cont_response_ok');
        } elseif (isset($result->status) && $result->status == "is_running") {
            $this->response['warning'] = $this->lang->line('generation_data_is_running');
        } else {
            $this->response['error'] = $this->lang->line('utm_cont_response_err');
        }
        $this->session->set_flashdata('messages', $this->response);
        redirect("dashboard");
    }

    public function realtime($max_lines = 10)
    {
        //checkPermission("dataclick-web/dashboard/realtime", true);

        //user data from session
        $data = $this->session->userdata;
        if(empty($data)){
            redirect(site_url().'main/login/');
        }
        // check token expired
        $is_expired = $this->user_model->isTokenExpired($data['id']);
        //print_r($is_expired);die;
        if($is_expired == "expired"){
            redirect('main/logout');
        }
        //check user level
        if(empty($data['role'])){
            redirect(site_url().'main/login/');
        }

        $data = [
            "utm" => $this->utm,
            "utms" => $this->UtmModel->findAll(),
            'json' => $this->dataclickapi->getRealTimeData([ "maxlines" => $max_lines ])
        ];
        //print_r($data['json']);die;
        $this->simpletemplate->render("dashboard_realtime", $data);
    }

    public function getRealtimeDataAjax($max_lines = 10)
    {

        $realtime_filter = [
            "maxlines" => $max_lines,
            "filter_online_navigation" => $this->input->post("filter_online_navigation"),
            "filter_ignore_online_navigation" => $this->input->post("filter_ignore_online_navigation"),
        ];

        $result = $this->dataclickapi->getRealTimeData($realtime_filter);
        $data = [
            "reportRT0001"   => isset($result->realtime_access) ? $this->makeGrid_RT0001($result->realtime_access) : '',
            "reportRT0002_1" => isset($result->realtime_access_face) ? $this->makeGrid_RT0002($result->realtime_access_face) : '',
            "reportRT0002_2" => isset($result->realtime_access_youtube) ? $this->makeGrid_RT0002($result->realtime_access_youtube) : '',
            "reportRT0002_3" => isset($result->realtime_access_insta) ? $this->makeGrid_RT0002($result->realtime_access_insta) : '',
            "reportRT0002_4" => isset($result->realtime_access_linke) ? $this->makeGrid_RT0002($result->realtime_access_linke) : '',
            "reportRT0002_5" => isset($result->realtime_access_twitter) ? $this->makeGrid_RT0002($result->realtime_access_twitter) : '',
            "reportRT0002_6" => isset($result->realtime_access_whatsapp) ? $this->makeGrid_RT0002($result->realtime_access_whatsapp) : '',
            "reportRT0003"   => isset($result->realtime_vpn) ? $this->makeGrid_RT0003($result->realtime_vpn) : '',
            "reportRT0004"   => isset($result->realtime_captive) ? $this->makeGrid_RT0004($result->realtime_captive) : ''
        ];
        echo json_encode($data);
    }

    public function makeGrid_RT0001($realtime_data)
    {
        if (empty($realtime_data)) {
            return;
        }

        $data = [];
        foreach (array_reverse($realtime_data) as $obj) {
            // GET CATEGORIES
            $categories = [];
            foreach (explode(",", $obj->categories_id) as $cat_id) {
                if (is_numeric($cat_id)) {
                    $categories[] = $this->lang->line('wf_cat_' . trim($cat_id));
                }
            }

            // if empty categories, set 99 = Others
            if (empty($categories)) {
                $categories[] = $this->lang->line('wf_cat_99');
            }

            $color = ($obj->status == "allowed") ? "style='background-color:#c6ffb3'" : "style='background-color:#ff9999'";
            $row = "<tr data-hash='{$obj->line_hash}'>";
            $row .= "<td {$color}>" . $obj->date_time . "</td>";
            $row .= "<td {$color}>" . (($obj->username == 'not referenced') ? $this->lang->line('utm_realtime_not_referenced') :  "<span class='badge'><i class='fa fa-user-o'></i> {$obj->username}</span>") . "</td>";
            $row .= "<td {$color}><a href='{$obj->url}' target='_blank'>" . (strlen($obj->url) > 50 ? substr($obj->url, 0, 50) . "..." : $obj->url) . "</a></td>";
            $row .= "<td {$color}><b>" . $this->lang->line('utm_realtime_' . $obj->status) . "</b></td>";
            $row .= "<td {$color}>" . implode(", ", $categories) . "</td>";
            $row .= "<td {$color}>{$obj->ipaddress}</td>";
            $row .= "<td {$color}>" . (($obj->groupname == 'not referenced') ? $this->lang->line('utm_realtime_not_referenced') :  $obj->groupname) . "</td>";
            $row .= "</tr>";

            $data[] = [
                "line_hash" => $obj->line_hash,
                "row" => $row
            ];
        }
        return json_encode($data);
    }

    public function makeGrid_RT0002($realtime_data)
    {
        if (empty($realtime_data)) {
            return;
        }

        $data = [];
        foreach($realtime_data as $obj) {
            $color = ($obj->status == "allowed") ? "style='background-color:#c6ffb3'" : "style='background-color:#ff9999'";
            $row  = "<tr data-hash='{$obj->line_hash}'>";
            $row .= "<td {$color}>" . $obj->date_time . "</td>";
            $row .= "<td {$color}><i class='fa fa-user-o'></i> {$obj->username}</td>";
            $row .= "<td {$color}>{$obj->ipaddress}</td>";
            $row .= "<td {$color}>" . (($obj->groupname == 'not referenced') ? $this->lang->line('utm_realtime_not_referenced') :  $obj->groupname) . "</td>";
            $row .= "</tr>";

            $data[] = [
                "line_hash" => $obj->line_hash,
                "row" => $row
            ];
        }
        return json_encode($data);
    }

    public function makeGrid_RT0003($realtime_data)
    {
        if (empty($realtime_data)) {
            return;
        }

        $html = "<table class='table text-left font-12'>";
        $html .= "<thead>";
        $html .= "<tr>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_username')) . "</th>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_real_ip')) . "</th>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_virtual_ip')) . "</th>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_connected')) . "</th>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_bytes_send')) . "</th>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_bytes_rec')) . "</th>";
        $html .= "</tr>";
        $html .= "</thead>";
        $html .= "<tbody>";

        foreach(array_reverse($realtime_data) as $obj) {
            $html .= "<tr>";
            $html .= "<td>{$obj->name}</td>";
            $html .= "<td>{$obj->remote_host}</td>";
            $html .= "<td>{$obj->virtual_addr}</td>";
            $html .= "<td>{$obj->connect_time}</td>";
            $html .= "<td>{$obj->bytes_sent}</td>";
            $html .= "<td>{$obj->bytes_recv}</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody>";
        $html .= "</table>";
        return $html;
    }


    
    public function makeGrid_RT0004($realtime_data)
    {
        if (empty($realtime_data)) {
            return "";
        }

        $user_agents_captive_portal = [
            'Win311' => "public/images/icons_so/windows.png",
            'Win95' => "public/images/icons_so/windows.png",
            'WinME' => "public/images/icons_so/windows.png",
            'Win98' => "public/images/icons_so/windows.png",
            'Win2000' => "public/images/icons_so/windows.png",
            'WinXP' => "public/images/icons_so/windows.png",
            'WinServer2003' => "public/images/icons_so/windows.png",
            'WinVista' => "public/images/icons_so/windows.png",
            'Windows 7' => "public/images/icons_so/windows.png",
            'Windows 8' => "public/images/icons_so/windows.png",
            'Windows 10' => "public/images/icons_so/windows.png",
            'WinNT' => "public/images/icons_so/windows.png",
            'OpenBSD' => "public/images/icons_so/openbsd.png",
            'SunOS' => "public/images/icons_so/sunos.png",
            'Ubuntu' => "public/images/icons_so/ubuntu.png",
            'Android' => "public/images/icons_so/android.png",
            'Linux' => "public/images/icons_so/linux.png",
            'iPhone' => "public/images/icons_so/iphone.png",
            'iPad' => "public/images/icons_so/ipad.png",
            'MacOS' => "public/images/icons_so/macos.png",
            'QNX' => "public/images/icons_so/qnx.png",
            'BeOS' => "public/images/icons_so/beos.png",
            'OS2' => "public/images/icons_so/os2.png",
        ];

        $html = "<table class='table text-left font-12'>";
        $html .= "<thead>";
        $html .= "<tr>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_username')) . "</th>";
        $html .= "<th>" . htmlentities('Login') . "</th>";
        $html .= "<th class='text-center'>" . htmlentities($this->lang->line('utm_cont_disp')) . "</th>";
        $html .= "<th>" . htmlentities('IP') . "</th>";
        $html .= "<th>" . htmlentities('mac') . "</th>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_connected')) . "</th>";
        $html .= "<th>" . htmlentities($this->lang->line('utm_cont_last_activ')) . "</th>";
        $html .= "</tr>";
        $html .= "</thead>";
        $html .= "<tbody>";

		$command = '/bin/cat /var/db/macverdor/mac-vendors-export.json | jq -jr \'.[] | .macPrefix, "---", .vendorName, "\n"\' > /tmp/macvendor';
		shell_exec($command);
		$pathIcon = "../../dataclick-web/public/images/icons_so";
		$array_imgs= ['aoc','apple','asus','dell','epson','hp','huawei','ibm','icon-profile-bright','icon-profile-dark',
		'lenovo','lg','logo','motorola','nokia','philco','raspberry','samsung','tcl','toshiba','xiaomi'];

		foreach($realtime_data as $rules) {
			foreach($rules as $obj) {
				$social_login = "local user";
				$user = $obj->username;

				if (strstr($user, 'facebook')) {
					$social_login = "facebook";
					$user = preg_match("/\:(.*)\(/", $user, $match) ? $match[1] : "invalid user";
				} elseif (strstr($user, 'form_auth')) {
					$social_login = "form_auth";
					$user = str_replace('form_auth:', '', $user);
				}

				$mac_vendor = strtoupper(substr($obj->mac,0,5));

				$command = "grep {$mac_vendor} /tmp/macvendor | awk -F\"---\" '{print \$2}' | head -n1";

				$result_vendor = shell_exec($command);

				if (($result_vendor == "unknown") || ($result_vendor == "")) {
					$result_vendor = "Network Interface";
				}

				$iconNow = "interface";
				$lower_comp = strtolower($result_vendor);

				foreach($array_imgs as $img_now) {
					$mystring = 'abc';
					if (strpos($lower_comp, $img_now) !== false) {
						$iconNow = $img_now;
						break;
					}
				}

				$iconCompany = "{$pathIcon}/{$iconNow}.png";
				$user_agent = "<img style='height:30px' src='{$iconCompany}'> <p>{$result_vendor}</p>";
			
				$html .= "<tr>";
				$html .= "<td style='vertical-align: middle;'>" . htmlentities($user) . "</td>";
				$html .= "<td style='vertical-align: middle;'>{$social_login}</td>";
				$html .= "<td style='vertical-align: middle;' class='text-center'>{$user_agent}</td>";
				$html .= "<td style='vertical-align: middle;'>{$obj->ip}</td>";
				$html .= "<td style='vertical-align: middle;'>{$obj->mac}</td>";
				$html .= "<td style='vertical-align: middle;'>" . date("d/m/Y H:i:s", strtotime($obj->connect_start)) . "</td>";
				$html .= "<td style='vertical-align: middle;'>" . date("d/m/Y H:i:s", strtotime($obj->last_activity)) . "</td>";
				$html .= "</tr>";
			}
		}
		$html .= "</tbody>";
		$html .= "</table>";
		return $html;
	}

    public function generate()
    {
        $post = $this->input->post();

        $data = [ 
            "report" => $post['report_id'] 
        ];

        if ($post['form'] == 'form_pre') {
            $data['period'] = $post['period'];
        } else {
            if (!$this->validateFormReportCustom()) {
                $response = [
                    "status" => "error",
                    "message" => validation_errors(),
                ];
                echo json_encode($response);
                exit;
            }
            $data['interval_from'] = $post['interval_from'];
            $data['interval_until'] = $post['interval_until'];
            /*if (empty($post['username'][0])) {
                $data['username'] = $post['username'][1];
            } else {
                $data['username'] = $post['username'][0];
            }*/
            $data['username'] = $post['username'];
            $data['ipaddress'] = $post['ipaddress'];
            $data['category_id'] = $post['category_id'];
            $data['groupname'] = isset($post['groupname']) ? $post['groupname'] : "";
        }
        $data['limit'] = $post['limit'];
        $data['format'] = $post['format'];

        if ($post['report_id'] == "P0007") {
            $data['social_network'] = "facebook";
        } elseif ($post['report_id'] == "P0008") {
            $data['social_network'] = "youtube";
        } elseif ($post['report_id'] == "P0009") {
            $data['social_network'] = "instagram";
        } elseif ($post['report_id'] == "P0010") {
            $data['social_network'] = "linkedin";
        }

        $params = serialize($data);

        $this->load->model("ReportsQueueModel");
        $data = [
            "report_id" => $post['report_id'],
            "params" => $params,
        ];
        if ($this->ReportsQueueModel->insert($data)) {
            $cmd = "nohup php index.php ReportsCommandLine index > /dev/null &";
            @exec($cmd, $out, $err);
            if (!$err) {
                $response = [
                    "status" => "ok",
                    "message" => $this->lang->line('reports_generating') . " " . getReports($post['report_id']) . "...",
                ];
            } else {
                $response = [ 
                    "status" => "error", 
                    "message" => $this->lang->line('reports_err_generating') . " " . getReports($post['report_id']) . "!",
                ];
            }
            echo json_encode($response);
        }
    }

    public function stopReport($pid)
    {
        $reports_pid = $this->getReportsPid();
        foreach ($reports_pid as $pid_file) {
            $_pid = trim(file_get_contents($pid_file));
            if ($_pid != $pid) {
                continue;
            }
            exec("pgrep -F {$pid_file}", $out, $err);
            if (isset($out[0]) && !empty($out[0])) {
                exec("kill -9 {$out[0]}", $out, $err);
                if (!$err) {
                    unlink($pid_file);
                }
            }
            break;
        }
    }

    public function checkReport()
    {
        $reports_pid = $this->getReportsPid();
        $reports_is_running = $this->getReportsInProcessing();
        if (!empty($reports_is_running)) {
            $data = [
                "running" => true,
                "total" => count($reports_is_running),
                "reports" => $reports_is_running
            ];
        } else {
            $data = [
                "running" => false,
            ];
        }
        echo json_encode($data);
    }

    public function getReportsInProcessing()
    {
        $reports_pid = $this->getReportsPid();
        $reports_in_processing = [];
        if (!empty($reports_pid)) {
            foreach ($reports_pid as $pidfile) {
                if (!file_exists($pidfile)) {
                    continue;
                }
                exec("pgrep -F {$pidfile}", $out, $err);
                if (!isset($out[0]) || empty($out[0])) {
                    unlink($pidfile);
                    continue;
                }
                preg_match("/report([E|P]?[0-9]+)/", $pidfile, $match);
                if (isset($match[1]) && !empty($match[1])) {
                    $reports_in_processing[] = [
                        'id' => $out[0],
                        'name' => $match[1] . " - " . getReports($match[1]) 
                    ];
                }
            }
        }
        return $reports_in_processing;
    }

    public function getReportsPid()
    {
        $reports_pid = glob("public/tmp/*report*.pid");
        return $reports_pid;
    }

    public function getReportFilesTable()
    {
        $this->load->model("ReportsQueueModel");
        $data = [
            "reports_queue" => $this->ReportsQueueModel->findAll(),
            "report_files" => $this->listReportsFiles(),
        ];
        $table = $this->load->view("reports/report_files", $data, true);
        echo $table;
    }

    public function getStateExportFiles() {
        $return_reports = [];
        foreach (glob("public/files/reports/*-E000*.csv") as $files_exports) {
            $file_line = end(explode('/', $files_exports));
            $type_report = explode(".", end(explode('-E000', $file_line)))[0];
            $time_stamp = explode('-', $file_line)[0];

            if (count(explode("\n", trim(file_get_contents($files_exports)))) > 1 ||
	            file_exists("/tmp/reportE000{$type_report}{$time_stamp}")) {
                continue;
            }

            $return_reports[] = $this->lang->line("reports_helpers_erel" . $type_report) . " (<a href='#{$file_line}'>" . $file_line . "</a>)";
        }
        echo implode("</br>", array_reverse($return_reports));
    }

    public function listReportsFiles()
    {
        $files = glob("public/files/reports/*");
        //Order desc by date
        array_multisort(array_map( 'filemtime', $files ), SORT_NUMERIC, SORT_DESC, $files);
        $report_files = [];
        if (!empty($files)) {
            foreach ($files as $file) {
                $filename = basename($file);
                $filename_without_extension = pathinfo($filename, PATHINFO_FILENAME);
                list($datetime, $report) = explode("-", $filename_without_extension);
                $report_files[] = [
                    "datetime" => date("d/m/Y H:i:s", $datetime),
                    "file" => $filename,
                    "filesize" => filesize($file),
                    "report" => $report . " - " . getReports($report),
                ];
            }
        }
        return $report_files;
    }

    public function downloadReport($filename)
    {
        $report_files = $this->listReportsFiles();
        foreach ($report_files as $rf) {
            if ($rf['file'] == $filename) {
                if (!file_exists("public/files/reports/{$filename}")) {
                    return;
                }
                downloadFile("public/files/reports/{$filename}", $rf['report']);
                break;
            }
        }
    }

    public function removeAllReports()
    {
        $files = glob('public/files/reports/*.*');
        foreach($files as $file){
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->response['ok'] = $this->lang->line('reports_success_removed_all');
        $this->session->set_flashdata('messages', $this->response);
        redirect("reports");
    }

    public function removeReport($filename)
    {
        if (file_exists("public/files/reports/{$filename}")) {
            unlink("public/files/reports/{$filename}");
            $this->response['ok'] = $this->lang->line('reports_success_removed');
        } else {
            $this->response['error'] = $this->lang->line('reports_file_not_search');
        }
        $this->session->set_flashdata('messages', $this->response);
        redirect("reports");
    }

    public function removeReportQueue($id)
    {
        $this->load->model("ReportsQueueModel");
        if ($this->ReportsQueueModel->delete($id)) {
            $this->response['ok'] = $this->lang->line('reports_queue_success_remove');
        } else {
            $this->response['error'] = $this->lang->line('reports_queue_error_remove');
        }
        $this->session->set_flashdata('messages', $this->response);
        redirect("reports");
    }

    private function validateFormReportCustom()
    {
        $config = [
            [
                'field' => 'interval_from',
                'rules' => 'required',
                'errors' => [
                    'required' => $this->lang->line('reports_provide_interv_from'),
                ]
            ],
            [
                'field' => 'interval_until',
                'rules' => 'required',
                'errors' => [
                    'required' => $this->lang->line('reports_provide_interv_until'),
                ]
            ],
        ];
        $this->form_validation->set_rules($config);
        if ($this->form_validation->run() !== FALSE) {
            return true;
        }
        return false;
    }

    public function utm_create()
    {
        $this->simpletemplate->render("utm/create");
    }

    public function utm_insert()
    {
        if (!$this->validateUTMForm()) {
            $this->utm_create();
        } else {
            $data = $this->input->post();
            if ($this->UtmModel->insert($data)) {
                $this->response['ok'] = $this->lang->line('utm_insert_ok_message');
                $this->utm_testConnectionSync($this->UtmModel->getIdInserted());
            } else {
                $this->response['error'] = $this->lang->line('utm_insert_error_message');
            }
            $this->session->set_flashdata('messages', $this->response);
            redirect("utm");
        }
    }

    public function utm_edit($utm_id)
    {
        $utm = $this->UtmModel->find($utm_id);
        if (!empty($utm)) {
            $this->simpletemplate->render("utm/edit", ["utm" => $utm]);
        } else {
            $this->response['error'] = $this->lang->line('utm_edit_error_message');
            $this->session->set_flashdata('messages', $this->response);
            redirect("utm");
        }
    }

    public function utm_update($utm_id)
    {
        if (!$this->validateUTMForm(['password'])) {
            $this->utm_edit($utm_id);
        } else {
            $data = $this->input->post();
            // If empty the password, not update
            if (empty($data['password'])) {
                unset($data['password']);
            }
            if ($this->UtmModel->update($utm_id, $data)) {
                $this->response['ok'] = $this->lang->line('utm_update_ok_message');
            } else {
                $this->response['error'] = $this->lang->line('utm_update_error_message');
            }
            $this->session->set_flashdata('messages', $this->response);
            redirect("utm");
        }
    }

    public function utm_remove($utm_id)
    {
        $utm = $this->UtmModel->find($utm_id);
        if (!empty($utm)) {
            if ($utm->is_default == 1) {
                $this->response['warning'] = $this->lang->line('utm_default_remove_error_message');
            } else {
                if ($this->UtmModel->delete($utm_id)) {
                    $this->response['ok'] = $this->lang->line('utm_remove_ok_message');
                } else {
                    $this->response['error'] = $this->lang->line('utm_remove_error_message');
                }
            }
        } else {
            $this->response['error'] = $this->lang->line('utm_remove_error_message');
        }
        $this->session->set_flashdata('messages', $this->response);
        redirect("utm");
    }

    public function utm_setDefault($utm_id)
    {
        if ($this->UtmModel->setDefault($utm_id)) {
            $this->response['ok'] = $this->lang->line('utm_set_default_ok_message');
        } else {
            $this->response['error'] = $this->lang->line('utm_set_default_error_message');
        }
        $this->session->set_flashdata('messages', $this->response);
        redirect("utm");
    }

    public function utm_testConnectionSync($utm_id)
    {
        $utm = $this->UtmModel->find($utm_id);
        if (!empty($utm)) {
            $data = [
                "protocol" => $utm->protocol,
                "host" => $utm->host,
                "port" => $utm->port,
                "user" => $utm->username,
                "pass" => $utm->password,
            ];
            $this->load->library("DataClickApi/DataClickApi", $data);
            $result = $this->dataclickapi->checkConnectAndSync();
            if (!empty($result)) {
                $this->response['ok'] = $this->lang->line('utm_test_conn_ok_message');

                // Update general logo of the UTM
                $logo =  $result->logo;
                $settings = [
                    "logo_name"    => $logo->name,
                    "logo_content" => $logo->content,
                    "serial"       => $result->serial,
                    "product_key"  => $result->product_key
                ];
                if ($this->UtmModel->update($utm_id, $settings)) {
                    $this->response['ok'] = $this->lang->line('utm_sync_ok_message');
                } else {
                    $this->response['error'] = $this->lang->line('utm_sync_error_message');
                }
            } else {
                $this->response['error'] = $this->lang->line('utm_test_conn_error_message');
            }
        } else {
            $this->response['alert'] = $this->lang->line('utm_test_conn_alert_message');
        }
        $this->session->set_flashdata('messages', $this->response);
        redirect("utm");
    }

    public function utm_changeDisplay($utm_id)
    {
        $utm = $this->UtmModel->find($utm_id);
        if (!empty($utm)) {
            $this->appsession->appendSession('utm_display', $utm->id);
            $this->response['ok'] = $this->lang->line('utm_change_display_ok_message');
        } else {
            $this->response['error'] = $this->lang->line('utm_change_display_error_message');
        }
        $this->session->set_flashdata('messages', $this->response);
        redirectBack();
    }

    private function validateUTMForm($ignore_fields = [])
    {
        $rule_valid_host = (preg_match("/^[0-9]+/", $this->input->post('host'))) ? "valid_ip" : "valid_url";
        $config = [
            [
                'field' => 'name',
                'label' => $this->lang->line('utm_create_name'),
                'rules' => 'required'
            ],
            [
                'field' => 'host',
                'label' => $this->lang->line('utm_create_host'),
                'rules' => 'required|' . $rule_valid_host
            ],
            [
                'field' => 'port',
                'label' => $this->lang->line('utm_create_port'),
                'rules' => 'required|integer'
            ],
            [
                'field' => 'username',
                'label' => $this->lang->line('utm_create_user'),
                'rules' => 'required'
            ],
            [
                'field' => 'password',
                'label' => $this->lang->line('utm_create_pass'),
                'rules' => 'required',
                'errors' => [
                    'required' => $this->lang->line('utm_form_validation') . ' %s.',
                ],
            ]
        ];
        if (!empty($ignore_fields)) {
            $i = 0;
            foreach ($config as $cf) {
                if (in_array($cf['field'], $ignore_fields)) {
                    unset($config[$i]);
                }
                $i++;
            }
        }
        $this->form_validation->set_rules($config);
        if ($this->form_validation->run() !== FALSE) {
            return true;
        }
        return false;
    }
}
