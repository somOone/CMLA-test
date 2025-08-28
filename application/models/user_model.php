<?php
require("assets/sendgrid/sendgrid-php.php");
class user_model extends CI_Model
{
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    function check_email($str)
    {
        $this->db->select('emailid');
        $this->db->from('cmla_member_login');
        $this->db->where('emailid', $str);
        
        $count = $this->db->count_all_results();
        
        if ($count > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
	function check_email_verify($code,$email)
    {
        $this->db->select('email_id');
        $this->db->from('cmla_member_email_verification');
        $this->db->where('verify_code', $code);
		 $this->db->where('email_id', $email);
        
        $count = $this->db->count_all_results();
        
        if ($count > 0)
        {
			$tables = array('cmla_member_email_verification');
			$this->db->where('email_id', $email);
			$this->db->delete($tables);
            return true;
        }
        else
        {
            return false;
        }
    }
	function pdf_generate($create_pdf = false,$member_id = '')
	{
		$member_id = $this->session->userdata('family_id');
		
		$qrys_member = $this->db->query("select *,
		(select ccm.center_name from cmla_center_master as ccm where ccm.id = cm.center_id order by id DESC limit 0,1) As center_names,
		(select ccm.center_address from cmla_center_master as ccm where ccm.id = cm.center_id order by id DESC limit 0,1) As center_addr
		from cmla_member as cm inner join cmla_member_child as cmc on cm.family_id = cmc.family_id 
		where cm.family_id = '".$member_id."'");
		$data['member_details'] = current($qrys_member->result_array());
		
		
		$qrys_payment = $this->db->query("select *,(select cop.token from cmla_online_payments as cop where cop.payment_id = cm.id order by id DESC limit 0,1) As transections
		from cmla_payments as cm where cm. family_id  = '".$member_id."'");
		$data['payment_details'] = current($qrys_payment->result_array());
		
		 $this->load->helper(array('dompdf', 'file'));
		 // page info here, db calls, etc.     
		 $html = $this->load->view('payment_pdf.php', $data, true);
		 $html = preg_replace('/>\s+</', '><', $html);

		 $content = pdf_create($html, '', false);

		 if ($create_pdf) {
			$path = FCPATH.'assets/pdfs/';
			
			if(!is_dir($path)) //create the folder if it's not already exists
			{
			  mkdir($path,0755,TRUE);
			}
		
			$file_name = $path.'Payment_receipt-'.$member_id.'.pdf';
			write_file($file_name, $content);
			return $file_name;
		 }

		header('Content-type: application/pdf');
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		header("Content-Disposition: inline;filename=Payment_receipt.pdf'");
		header("Content-length: ".strlen($content));

		echo $content;

		 exit;
	}
	function get_payment_details($ids)
	{
		$qrys_payment = $this->db->query("select *,(select cop.token from cmla_online_payments as cop where cop.payment_id = cm.id order by id DESC limit 0,1) As transections from cmla_payments as cm where cm.id  = '".$ids."' order by id DESC");
		$payment_result = current($qrys_payment->result_array());
		
		return $payment_result;
	}
	function get_member_details($member_id)
	{
		 $qrys_member = $this->db->query("SELECT *,(select cmm.session_name from cmla_center_session_master as cmm where cmm.id = cml.session_id)as session_name, (select cmy.name from cmla_member_login as cmy where cmy.id = cm.member_login_id AND cm.member_type = 1) as prime_name, (select cmy.name from cmla_member_login as cmy where cmy.id = cm.member_login_id AND cm.member_type = 2) as sec_prime_name, (select ccm.center_name from cmla_center_master as ccm where ccm.id = cm.center_id order by id DESC limit 0,1) As center_names,(select ccm.center_address from cmla_center_master as ccm where ccm.id = cm.center_id order by id DESC limit 0,1) As center_addr FROM cmla_member_login as cml inner join cmla_member as cm on cml.id = cm.member_login_id inner join cmla_member_child as cmc on cm.family_id = cmc.family_id where cm.family_id = '".$member_id."'");
		//$member_result = $qrys_member->result_array();
		
		/*$qrys_member = $this->db->query("select *,
		(select ccm.center_name from cmla_center_master as ccm where ccm.id = cm.center_id order by id DESC limit 0,1) As center_names,
		(select ccm.center_address from cmla_center_master as ccm where ccm.id = cm.center_id order by id DESC limit 0,1) As center_addr
		from cmla_member as cm inner join cmla_member_child as cmc on cm.family_id = cmc.family_id 
		where cm.family_id = '".$member_id."'");*/
		$member_result = current($qrys_member->result_array());
		
		return $member_result;
	}
	
	function login_get_data($email,$pass)
    {
		
		return $this->db->get_where('users', array('email'=>$email,'pass'=>md5($pass)))->row();
		
	}
	
	function get($id)
    {
        return $this->db->get_where('users', array('id'=>$id))->row();
		
	}
	function get_email_valid($email)
    {
		$this->db->select('emailid');
		$this->db->from('cmla_member_login');
		$this->db->where('emailid', $email);
		$count = $this->db->count_all_results();
		if ($count > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
		
    }
	
    function insertVerification($data)
    {		
		  $this->db->insert('cmla_member_email_verification', $data);
		  $insert_id = $this->db->insert_id();
		  //$this->db->last_query();
           		  
          return  $insert_id;
    }
	function deleteVerification($email)
    {		
		$tables = array('cmla_member_email_verification');
		$this->db->where('email_id', $email);
		$this->db->delete($tables);
		return true;
    }
    //insert into user table
    function insertMemberLogin($login_data)
    {
		
		  $this->db->insert('cmla_member_login', $login_data);
		  $insert_id = $this->db->insert_id();
		  //$this->db->last_query();
		 
          return  $insert_id;
        //return $this->db->insert('cmla_member_login', $data);
    }
	
	//insert into user table
    function insertMember($data)
    {
		
		  $this->db->insert('cmla_member', $data);
		  $insert_id = $this->db->insert_id();
		  //$this->db->last_query();
          return  $insert_id;
        //return $this->db->insert('cmla_member_login', $data);
    } 
	
	//insert into user table
    function insertTrust($data)
    {
		
		  $this->db->insert('cmla_member_trust', $data);
		  $insert_id = $this->db->insert_id();
          return  $insert_id;
        //return $this->db->insert('cmla_member_login', $data);
    } 
	
	
	//selectt into user table
    function selectUser($data)
    {
        //return $this->db->insert('users', $data);
    }
    
    //send verification email to user's email id
    function sendEmail($to_email,$msg)
    {
        $from_email = 'info@chinmayala.com'; //change this to yours
        $subject = 'Your Email Verification Code';
        //$message = 'Hari Om <br /><br />Your Email Verification Code for Member/BV Registration is.<font color="red">&nbsp;'.$msg.'</font><br /><br /><br />Thanks<br />CMLA Admin';       
        //configure email settings
		
		   $message = '<div style="max-width: 800px; margin: auto;">
        <table style="border:3px #428bca solid; width: 100%; margin: auto;" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <table style="width: 100%; background-color: #428bca; padding:15px;" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                               <img src="'.base_url().'/assets/logo.png">
                            </td>
                            <td style="color: #fff; font-size: 23px;" align="right">
                             
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td>
                    <table style="width: 100%; padding: 20px;" cellpadding="0" cellspacing="0">
  <tr>   
                            <td style="font-size: 15px;padding: 20px 0px;">
							<p style="font-size: 17px;"><b>Hari Om </b></p>
                           Your Email Verification Code for Member/BV Registration is.<font color="red">&nbsp;'.$msg.'</font>
                            </td>
                        </tr>
               
                       
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <table style="width: 100%; background-color: #428bca; padding:20px 15px; color: #fff;" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                Chinmaya Mission Los Angeles is a 501(C)3 non profit organization Federal Tex ID#95-4346411. No goods or services were received in consideration of this donation.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>';
		
		
		
            /*$config['mailtype']     = 'html'; // or html
          
            $config['protocol']     = 'smtp';

            $config['smtp_host'] = 'ssl://smtp.googlemail.com'; //smtp host name
            
            $config['smtp_port'] =  465; //smtp port number
            
            $config['smtp_user'] = 'bv.cmla@gmail.com';
            
            $config['smtp_pass'] = 'asd#123#asd'; //$from_email password

            $config['charset']      = 'utf-8';

            $config['newline']      = "\r\n";

            $config['mailtype']     = 'html'; // or html

            $config['validation']   = TRUE;
			
		$this->email->initialize($config);
		$this->load->library('email', $config);
        
        //send mail
		$this->email->set_mailtype("html");
        $this->email->from($from_email, 'CMLA BV System Admin');
        $this->email->to($to_email);
		$this->email->bcc('cmla.community@gmail.com');
        $this->email->subject($subject);
        $this->email->message($message);
		if($this->email->send()){
			
			$val = 1;
			return $val;
			exit;
		}else{
			show_error($this->email->print_debugger());
		}*/
		
		$email = new \SendGrid\Mail\Mail(); 
		$email->setFrom("info@chinmayala.com", "CMLA BV System Admin");
		$email->setSubject($subject);
		$email->addTo($to_email);
		//$email->addCc('vijay@ravemaxpro.co.in');
		$email->addBcc('cmla.community@gmail.com,vijay@ravemaxpro.co.in,sivagurunath.s@ravemaxpro.in,sivagurunath77@gmail.com');
		//$email->addAttachment($attach_path);
		$email->addContent("text/html",$message);
		$sendgrid = new \SendGrid($this->config->item('sendgrid_api_key'));
		try {
			$response = $sendgrid->send($email);
		} catch (Exception $e) {
			echo 'Caught exception: '. $e->getMessage() ."\n";
		}
		
		$reponseValue =  $response->statusCode();

		if($reponseValue == '202' || $reponseValue == '200')
		{
			$val = 1;
			return $val;
		} 
		exit;
        
    }
	
	function payment_sendMail($subject,$emails,$message,$pdf_files)
	{
		// //change this to yours
        //$subject = 'Your Email Verification Code';
        //$message = 'Hari Om <br /><br />Your Email Verification Code for Member/BV Registration is.<font color="red">&nbsp;</font><br /><br /><br />Thanks<br />CMLA Admin';  
		
        $from_email = 'bv.cmla@gmail.com';
		
		//$from_email = 'info@chinmayala.com';


       /* $config['protocol'] = 'smtp';
        $config['smtp_host'] = 'ssl://smtp.googlemail.com'; //smtp host name
        $config['smtp_port'] = 465; //smtp port number
        $config['smtp_user'] = 'bv.cmla@gmail.com';
        $config['smtp_pass'] = 'asd#123#asd'; //$from_email password
        $config['mailtype'] = 'html';
        $config['charset'] = 'iso-8859-1';
        $config['wordwrap'] = TRUE;
        $config['newline'] = "\r\n"; //use double quotes
        $this->email->initialize($config);
        $this->load->library('email', $config);
		
        //send mail
		$this->email->set_mailtype("html");
        $this->email->from($from_email, 'CMLA BV System Admin');
        $this->email->to($emails);
		$this->email->bcc('cmla.community@gmail.com');
        $this->email->subject($subject);
        $this->email->message($message);
		$this->email->attach($pdf_files);
		if($this->email->send()){
			$val = 1;
			return $val;
			exit;
		}else{
			show_error($this->email->print_debugger());
		}*/
		
		
		$email = new \SendGrid\Mail\Mail(); 
		$email->setFrom("info@chinmayala.com", "CMLA BV System Admin");
		$email->setSubject($subject);
		$email->addTo($emails);
		//$email->addCc('vijay@ravemaxpro.co.in,sivagurunath.s@ravemaxpro.in');
		$email->addBcc('cmla.community@gmail.com,vijay@ravemaxpro.co.in,sivagurunath.s@ravemaxpro.in,sivagurunath77@gmail.com');
		//$email->addAttachment($pdf_files);
		$email->addContent("text/html",$message);
		$sendgrid = new \SendGrid($this->config->item('sendgrid_api_key'));
		try {
			$response = $sendgrid->send($email);
		} catch (Exception $e) {
			echo 'Caught exception: '. $e->getMessage() ."\n";
		}
		
		$reponseValue =  $response->statusCode();

		if($reponseValue == '202' || $reponseValue == '200')
		{
			$val = 1;
			return $val;
			exit;
		} 
       
	}
	function payment_sendMail_pff($subject,$emails,$message,$pdf_file)
	{
	    //change this to yours
        //$subject = 'Your Email Verification Code';
        //$message = 'Hari Om <br /><br />Your Email Verification Code for Member/BV Registration is.<font color="red">&nbsp;</font><br /><br /><br />Thanks<br />CMLA Admin';

       	
        $from_email = 'bv.cmla@gmail.com';
		
		//$from_email = 'info@chinmayala.com';		
		
       /* $config['protocol'] = 'smtp';
        $config['smtp_host'] = 'ssl://smtp.googlemail.com'; //smtp host name
        $config['smtp_port'] = 465; //smtp port number
        $config['smtp_user'] = 'bv.cmla@gmail.com';
        $config['smtp_pass'] = 'asd#123#asd'; //$from_email password
        $config['mailtype'] = 'html';
        $config['charset'] = 'iso-8859-1';
        $config['wordwrap'] = TRUE;
        $config['newline'] = "\r\n"; //use double quotes
        $this->email->initialize($config);
        $this->load->library('email', $config);
      
        //send mail
		$this->email->set_mailtype("html");
        $this->email->from($from_email, 'CMLA BV System Admin');
        $this->email->to($emails);
		$this->email->bcc('cmla.community@gmail.com');
        $this->email->subject($subject);
        $this->email->message($message);
		$this->email->attach($pdf_file);
		if($this->email->send()){
			$val = 1;
			return $val;
			exit;
		}else{
			show_error($this->email->print_debugger());
		}*/
		
		$email = new \SendGrid\Mail\Mail(); 
		$email->setFrom("info@chinmayala.com", "CMLA BV System Admin");
		$email->setSubject($subject);
		$email->addTo($emails);
		//$email->addCc('vijay@ravemaxpro.co.in,sivagurunath.s@ravemaxpro.in');
		$email->addBcc('cmla.community@gmail.com,vijay@ravemaxpro.co.in,sivagurunath.s@ravemaxpro.in,sivagurunath77@gmail.com');
		//$email->addAttachment($pdf_file);
		$email->addContent("text/html",$message);
		$sendgrid = new \SendGrid($this->config->item('sendgrid_api_key'));
		try {
			$response = $sendgrid->send($email);
		} catch (Exception $e) {
			echo 'Caught exception: '. $e->getMessage() ."\n";
		}
		
		$reponseValue =  $response->statusCode();

		if($reponseValue == '202' || $reponseValue == '200')
		{
			$val = 1;
			return $val;
			exit;
		} 
       
	}
	function payment_sendMail_without_pdf($subject,$emails,$message)
	{
		 //change this to yours
        //$subject = 'Your Email Verification Code';
        //$message = 'Hari Om <br /><br />Your Email Verification Code for Member/BV Registration is.<font color="red">&nbsp;</font><br /><br /><br />Thanks<br />CMLA Admin';

        $from_email = 'bv.cmla@gmail.com';
		
		//$from_email = 'info@chinmayala.com';			
		
        /*$config['protocol'] = 'smtp';
        $config['smtp_host'] = 'ssl://smtp.googlemail.com'; //smtp host name
        $config['smtp_port'] = 465; //smtp port number
        $config['smtp_user'] = 'bv.cmla@gmail.com';
        $config['smtp_pass'] = 'asd#123#asd'; //$from_email password
        $config['mailtype'] = 'html';
        $config['charset'] = 'iso-8859-1';
        $config['wordwrap'] = TRUE;
        $config['newline'] = "\r\n"; //use double quotes
        $this->email->initialize($config);
        $this->load->library('email', $config);
		
        //send mail
		$this->email->set_mailtype("html");
        $this->email->from($from_email, 'CMLA BV System Admin');
        $this->email->to($emails);
		//$this->email->to('cmla.community@gmail.com');
        $this->email->bcc('cmla.community@gmail.com');
        $this->email->subject($subject);
        $this->email->message($message);
		//$this->email->attach($pdf_file);
		if($this->email->send()){
			$val = 1;
			return $val;
			exit;
		}else{
			show_error($this->email->print_debugger());
		}*/
		
		$email = new \SendGrid\Mail\Mail(); 
		$email->setFrom("info@chinmayala.com", "CMLA BV System Admin");
		$email->setSubject($subject);
		$email->addTo($emails);
		//$email->addCc('vijay@ravemaxpro.co.in');
		$email->addBcc('cmla.community@gmail.com,vijay@ravemaxpro.co.in,sivagurunath.s@ravemaxpro.in,sivagurunath77@gmail.com');
		//$email->addAttachment($pdf_file);
		$email->addContent("text/html",$message);
		$sendgrid = new \SendGrid($this->config->item('sendgrid_api_key'));
		try {
			$response = $sendgrid->send($email);
		} catch (Exception $e) {
			echo 'Caught exception: '. $e->getMessage() ."\n";
		}
		
		$reponseValue =  $response->statusCode();

		if($reponseValue == '202' || $reponseValue == '200')
		{
			$val = 1;
			return $val;
			exit;
		} 
       
	}
    
    //activate user account
    function verifyEmailID($key)
    {
        $data = array('is_active' => 1);
        $this->db->where('md5(emailid)', $key);
        return $this->db->update('cmla_member_login', $data);
    }
	function updateChild($data,$childId)
    {
        $this->db->where('id', $childId);
        return $this->db->update('cmla_member_child', $data);
    }
	
	
    //udate secondary member details
    function updateMember($data,$memberId)
    {
        $this->db->where('member_login_id', $memberId);
        return $this->db->update('cmla_member', $data);
    }
    function updateMemberExtra($data,$memberId)
    {
        $this->db->where('id', $memberId);
        return $this->db->update('cmla_member_login', $data);
    }

    function updateTrustOfMember($data,$trust_id)
    {
        $this->db->where('trust_id', $trust_id);
        return $this->db->update('cmla_member_trust', $data);
    }
    function updateMemberName($data,$member_login_id)
    {
        $this->db->where('id', $member_login_id);
        return $this->db->update('cmla_member_login', $data);
    }
    function updateMemberDetails($data,$member_login_id)
    {
        $this->db->where('member_login_id', $member_login_id);
        return $this->db->update('cmla_member', $data);
		
    }
	function updateMemberCenter($data,$family_id)
    {
        
		$this->db->where('family_id', $family_id);
        $this->db->update('cmla_member', $data);
		
		
		$this->db->where('family_id', $family_id);
        $this->db->update('cmla_member_login', $data);
		
		$this->db->where('family_id', $family_id);
        $this->db->update('cmla_member_child', $data);
		
		
		return true;
    }
    function amountRefundRequest($payment_id)
    {
        $data = array(
            'is_amnt_refund_requested' => '1',
            'refund_status' => 'pending'
            );

        $this->db->where('id', $payment_id);
        return $this->db->update('cmla_payments', $data);
    }
    function getCenterSession($center_id = null){
         $this->db->select('id, session_start,session_end,session_name');
         
         if($center_id != NULL){
            $this->db->where('center_id', $center_id);
         }
         
         $query = $this->db->get('cmla_center_session_master');
         
         $sessions = array();
         
         if($query->result()){
             foreach ($query->result() as $session) {
                $sessions[$session->id] = $session->session_name.' ('.$session->session_start.'-'.$session->session_end.')';
             }
            return $sessions;
         }else{
            return FALSE;
         }
    }
    function getBVClass($center_id = null,$grade_id=null){
         $this->db->select('id, class_name');
         
         if($center_id != NULL){
            $this->db->where('center_id', $center_id);
            $this->db->where('grade_id', $grade_id);
            
         }
         
         $query = $this->db->get('cmla_class_master');
         
         $class = array();
         
         if($query->result()){
             foreach ($query->result() as $cls) {
                $class[$cls->id] = $cls->class_name;
        }
            return $class;
         }else{
            return FALSE;
         }
    }
   
    function insertReassignmentData($data)
    {
        $this->db->insert('cmla_reassignment_request_table', $data);
        $insert_id = $this->db->insert_id();
        return  $insert_id;
    }
    function updateReassignmentData($childId,$data)
    {
        $this->db->where('child_id',$childId);
        return $this->db->update('cmla_reassignment_request_table', $data);
       
    }
    function getGrades()
    {
        $this->db->select('cmla_bv_grade_master.*');
        $this->db->from('cmla_bv_grade_master');
        $result = $this->db->get();
        return $result->result();
    }
    function updateProfileImage($id,$filename)
    {
        $data = array('image' => $filename);
        $this->db->where('id', $id);
        return $this->db->update('cmla_member_login', $data);
    }
	function getUserRoleAccessPageList($arrRole)
	{
		$this->db->select('access_page_id');
        $this->db->from('cmla_role_master');
		$this->db->where_in('id',$arrRole);
        $result = $this->db->get();
        return $result->result_array();
	}
	
	function getUserRoleAccessPageList_admin($arrRole)
	{
		$this->db->select('access_page_id');
        $this->db->from('cmla_role_master');
		$this->db->where_in('id',$arrRole);
        $result = $this->db->get();
        return $result->row_array();
	}
	function getPageNameByPageId($pageId)
	{
		$this->db->select('slug,page_name');
        $this->db->from('cmla_role_leftside_page');
		$this->db->where_in('id',$pageId);
        $result = $this->db->get();
        return $result->result_array();
	}
	
    
}
?>