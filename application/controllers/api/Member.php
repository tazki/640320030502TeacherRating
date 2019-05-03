<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Member extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->controller = strtolower(get_class($this));
        $this->data['controller'] = $this->controller;
        $this->data['uri_string'] = $this->uri->uri_string();
        $this->data['get_query'] = $this->input->get(NULL, TRUE);
        $this->load->model('Base_model');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        // $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        // $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        // $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
        // $this->methods['event_post']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function index_get()
    {
        // api/member/{teacher,student}?q={first_name,last_name}&per_page=1&sort={user_created_at,user_first_name,user_last_name}&order={asc,desc}
        $type = $this->get('type', TRUE);
        $search_query = '`user_group_id` = "6"';
        if($type=='student')
        {
            $search_query = '`user_group_id` = "7"';
        }
        elseif($type=='appadmin')
        {
            $search_query = '`user_group_id` = "8"';
        }

        $search_query .= 'AND `user_trashed_at` IS NULL';
        
        $search = $this->get('q', TRUE);        
        if(!empty($search))
        {
            $search_query .= ' AND (`user_first_name` LIKE "%'.$search.'%"
                                    OR `user_last_name` LIKE "%'.$search.'%")';
        }
        $total_segments = $this->uri->total_segments();
        $total_count = $this->Base_model->count('tz_users', $search_query);
        $this->data['total_count'] = $total_count;
        $pagination = paginationForApi($this->controller, $total_count, $total_segments, $this->input->get('per_page', TRUE));
        $sort = $this->input->get('sort', TRUE);
        $sort = (!empty($sort)) ? $sort : 'user_created_at';
        $order = $this->input->get('order', TRUE);
        $rows = $this->Base_model->list_all('tz_users', $pagination['per_page'], $this->uri->segment($total_segments), $sort, $order, $search_query);
        if(is_array($rows))
        {
            $teacher_ids = '';
            foreach($rows as $key => $val)
            {
                $comma = (!empty($teacher_ids)) ? ',' : '';
                $teacher_ids .= $comma.$val['user_id'];

                $data['rows'][$val['user_id']] = $val;
                $data['rows'][$val['user_id']]['user_created_at'] = dateformat($val['user_created_at'], 'Y/m/d');

                // $cond['student_id'] = $val['user_id'];
                // $tz_section_to_student = $this->Base_model->search_one($cond, 'tz_section_to_student');
                // if(isset($tz_section_to_student['section_id']))
                // {
                //     $data['rows'][$key]['section_id'] = $tz_section_to_student['section_id'];
                // }
            }

            #SOS total teacher rating
            $search_query = '`teacher_id` IN('.$teacher_ids.')';
            $query = $this->db->query(
                'SELECT `rts`.`rating_to_teacher_id`,
                    AVG(survey_rating) as `rating_average`,
                    teacher_id,DATE_FORMAT(rating_created_at, "%Y-%m") as rating_created_at
                FROM tz_rating_to_teacher rtt
                LEFT JOIN tz_rating_to_survey rts ON `rts`.`rating_to_teacher_id` = `rtt`.`rating_to_teacher_id`
                WHERE '.$search_query.'
                GROUP BY `rts`.`rating_to_teacher_id`'
            );
            $rating_to_teacher_survey = $query->result_array();
            $query->free_result();
            if(is_array($rating_to_teacher_survey))
            {
                foreach($rating_to_teacher_survey as $key => $val)
                {
                    $prep_rating_to_teacher[$val['teacher_id']][$val['rating_created_at']][$key] = $val;
                }
                // prearr($prep_rating_to_teacher);

                if(isset($prep_rating_to_teacher))
                {
                    foreach($prep_rating_to_teacher as $teacher_id => $val)
                    {   
                        $total_month = 0;
                        $total_rating_average = 0;
                        foreach($val as $rating_created_at => $sval)
                        {
                            $rating_average = 0;    
                            foreach($sval as $tkey => $tval)
                            {
                                $rating_average += (float)$tval['rating_average'];
                            }

                            $total_month++; 
                            $total_rating = sizeof($sval);
                            $total_average_per_month = $rating_average / $total_rating;
                            $total_rating_average += $total_average_per_month;
                            
                            // echo "\nteacher_id:".$teacher_id.' rating_created_at:'.$rating_created_at.' sizeof:'.$total_rating.' '.$rating_average;
                            // echo "\n".'average:'.$total_average."\n";
                            // echo "\ntotal_month:".$total_month;
                            
                            $data['rows'][$teacher_id]['total_average_per_month'][$rating_created_at] = $total_average_per_month;
                        }
                        $total_average = $total_rating_average / $total_month;
                        $data['rows'][$teacher_id]['total_average'] = $total_average;
                    }
                }
                // prearr($data['rows']);
            }
            #EOS total teacher rating
        }

        $data['pagination'] = $pagination['pagination'];
        $data['current_page'] = $pagination['current_page'];
        $data['total_page'] = $pagination['total_page'];
        $data['total_rows'] = $pagination['total_rows'];
        $data['per_page'] = $pagination['per_page'];
        $data['search_string'] = '?'.http_build_query($this->input->get(NULL, TRUE));
        $this->set_response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code        
    }

    public function detail_get()
    {
        $id = $this->get('id');
        // Find and return a single record for a particular user.
        $id = (int) $id;
        if($id <= 0)
        {
            // Invalid id, set the response and exit.
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }
        else
        {
            $row = $this->Base_model->list_all('tz_users', '', '', '', '', 'user_id="'.$id.'"');
            if(is_array($row[0]))
            {
                // $cond['student_id'] = $id;
                // $tz_section_to_student = $this->Base_model->search_one($cond, 'tz_section_to_student');
                // if(isset($tz_section_to_student['section_id']))
                // {
                //     $row[0]['section_id'] = $tz_section_to_student['section_id'];
                // }

                #SOS total teacher rating
                $search_query = '`teacher_id` = "'.$id.'"';
                $query = $this->db->query(
                    'SELECT `rtt`.`rating_to_teacher_id`,
                        DATE_FORMAT(`rtt`.rating_created_at, "%M %Y") as rating_created_at,
                        `rts`.`survey_id`,
                        `rts`.`survey_rating`
                    FROM tz_rating_to_teacher rtt
                    LEFT JOIN tz_rating_to_survey rts ON `rts`.`rating_to_teacher_id` = `rtt`.`rating_to_teacher_id`
                    WHERE '.$search_query
                );
                $rating_to_teacher_survey = $query->result_array();
                $query->free_result();
                // prearr($rating_to_teacher_survey);
                #group them by date and rate form
                $survey_ids = '';
                foreach($rating_to_teacher_survey as $key => $val)
                {
                    $comma = (!empty($survey_ids)) ? ',' : '';
                    $survey_ids .= $comma.$val['survey_id'];
                    $prep_rating_to_teacher[$val['rating_created_at']][$val['survey_id']][$val['rating_to_teacher_id']] = $val['survey_rating'];
                }
                // prearr($prep_rating_to_teacher);
                
                #add all survey rating with the same month
                if(isset($prep_rating_to_teacher) && is_array($prep_rating_to_teacher))
                {
                    $final_rating_to_teacher = array();
                    $total_average_per_survey = 0;
                    foreach($prep_rating_to_teacher as $rating_created_at => $val)
                    {
                        foreach($val as $survey_id => $sval)
                        {
                            // if(isset($prep_survey[$survey_id]))
                            // {
                            //     $final_rating_to_teacher[$rating_created_at]['rating_per_survey'][$survey_id]['survey'] = $prep_survey[$survey_id];
                            // }

                            #compute average per survey
                            $average_per_survey = round(array_sum($sval) / sizeof($sval), 2);
                            $final_rating_to_teacher[$rating_created_at]['rating_per_survey'][$survey_id]['survey_average'] = $average_per_survey;

                            $total_average_per_survey += (float)$average_per_survey;
                        }
                        #compute total average per survey
                        $final_rating_to_teacher[$rating_created_at]['total_average_per_survey'] = round($total_average_per_survey / sizeof($val), 2);
                    }
                    // prearr($final_rating_to_teacher);
                    $row[0]['rating_per_month'] = $final_rating_to_teacher;

                    #get survey questions
                    if(!empty($survey_ids))
                    {
                        $search_query = '`survey_id` IN('.$survey_ids.')';
                        $query = $this->db->query(
                            'SELECT *
                            FROM `tz_survey`
                            WHERE '.$search_query.'
                            ORDER BY `survey_sort` asc'
                        );
                        $survey_rows = $query->result_array();
                        $query->free_result();
                        if(is_array($survey_rows))
                        {
                            $prep_survey = array();
                            foreach($survey_rows as $key => $val)
                            {
                                $prep_survey[$val['survey_id']] = $val['survey_question'];
                            }
                        }
                    }
                    $row[0]['survey_question'] = $prep_survey;                    
                }
                #EOS total teacher rating

                

                $this->set_response($row[0], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
            }
            else
            {
                $this->set_response([
                    'status' => FALSE,
                    'message' => 'User could not be found'
                ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
            } 
        }
    }
    
    public function login_post()
    {
        $this->load->library('form_validation');
        $post_data = $this->input->post(null, false);
        if(sizeof($_POST) > 0)
        {
            $message['status'] = 'danger';
            $message['alert'] = 'Fill all the fields.';

            $this->config_login = array(
                array(
                    'field'   => 'user_username',
                    'label'   => 'Username',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'user_password',
                    'label'   => 'Password',
                    'rules'   => 'trim|required'
                )
            );
            $this->form_validation->set_rules($this->config_login);
            if($this->form_validation->run() == true)
            {
                $cond['user_trashed_by'] = 0;
                $cond['user_group_id'] = 8;
                $cond['user_current_status_id'] = 2;
                $cond['user_username'] = $post_data['user_username'];
                $cond['user_password'] = do_hash($post_data['user_password'], 'md5');
                $row = $this->Base_model->search_one($cond, 'tz_users');
                if(isset($row) && is_array($row))
                {
                    $cond = array('user_id' => $row['user_id']);
                    $user_logged_in = array('user_is_login' => 1);
                    $this->Base_model->update($user_logged_in, $cond, 'tz_users');
                    $row['user_language_id'] = 1;
                    $message['row'] = $row;
                    $message['status'] = 'success';
                    $message['alert'] = 'Welcome back!';
                }
                else
                {
                    $message['status'] = 'danger';
                    $message['alert'] = 'Username or Password Incorrect!';
                }
            }
            else
            {
                #array form variables need to be declare as array
                $message = array();
                $message['status'] = 'danger';
                foreach($post_data as $field_name => $field_val)
                {
                    $error_msg = form_error($field_name, '<span class="error">', '</span>');
                    if(!empty($error_msg))
                    {
                        $message['alert'][$field_name] = $error_msg;   
                    }
                }
            }
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function forgotpassword_post()
    {
        $this->load->library('form_validation');
        $post_data = $this->input->post(null, false);
        if(sizeof($_POST) > 0)
        {
            $message['status'] = 'danger';
            $message['alert'] = 'Fill all the fields.';

            $count = 0;
            foreach($post_data as $field_name => $field_val)
            {
                $config_data[$count]['field'] = $field_name;
                $config_data[$count]['label'] = ucwords(str_replace(array('user_', '_'), array('', ' '), $field_name));
                $config_data[$count]['rules'] = 'trim|required|valid_email';
                $count++;
            }
            $this->config_data = $config_data;
            $this->form_validation->set_rules($this->config_data);
            if($this->form_validation->run() == true)
            {
                $cond['user_email_address'] = $post_data['user_email_address'];
                $row = $this->Base_model->search_one($cond, 'tz_users');
                if(isset($row) && is_array($row))
                {
                    $message['status'] = 'success';
                    $message['alert'] = 'Password Reset already sent on your email';

                    $this->load->library('email');
                    $config['useragent'] = 'Teacher Admin';
                    $config['mailtype'] = 'html';
                    $config['charset'] = 'utf-8';
                    $config['wordwrap'] = TRUE;
                    $this->email->initialize($config);
                    $this->email->from('support@tinkermak.com', '');
                    $this->email->to($post_data['user_email_address']);
                    // $this->email->cc('another@another-example.com');
                    $this->email->bcc('support@tinkermak.com');

                    $this->email->subject('Password Reset');
                    $post_data['encoded_email'] = urlencode(base64_encode($row['user_email_address'].'|'.$row['user_id']));
                    $this->email->message($this->load->view('api/mail_forgot_password', $post_data, true));
                    $this->email->send();
                }
                else
                {
                    $message['status'] = 'danger';
                    $message['alert'] = 'Email Address is Incorrect!';
                }
            }
            else
            {
                #array form variables need to be declare as array
                $message = array();
                $message['status'] = 'danger';
                foreach($post_data as $field_name => $field_val)
                {
                    $error_msg = form_error($field_name, '<span class="error">', '</span>');
                    if(!empty($error_msg))
                    {
                        $message['alert'][$field_name] = $error_msg;
                    }
                }
            }
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function resetpassword_get($email)
    {
        $message['form_url'] = site_url('api/member/resetpassword/'.$email);
        $this->load->view('api/mail_reset_password', $message);
    }

    public function resetpassword_post($email)
    {        
        $this->load->library('form_validation');
        $post_data = $this->input->post(null, false);
        if(sizeof($_POST) > 0)
        {
            $message['status'] = 'danger';
            $message['alert'] = 'Fill all the fields.';

            $count = 0;
            foreach($post_data as $field_name => $field_val)
            {
                if($field_name == 'user_confirm_password')
                {
                    $config_data[$count]['field'] = $field_name;
                    $config_data[$count]['label'] = ucwords(str_replace(array('user_', '_'), array('', ' '), $field_name));
                    $config_data[$count]['rules'] = 'trim|required|matches[user_password]';
                    $count++;
                }
                else
                {
                    $config_data[$count]['field'] = $field_name;
                    $config_data[$count]['label'] = ucwords(str_replace(array('user_', '_'), array('', ' '), $field_name));
                    $config_data[$count]['rules'] = 'trim|required';
                    $count++;
                }
            }
            $this->config_data = $config_data;
            $this->form_validation->set_rules($this->config_data);
            if($this->form_validation->run() == true)
            {                
                $user_password['user_password'] = do_hash($post_data['user_password'], 'md5');
                $arr_tmp = explode('|', base64_decode(urldecode($email)));
                $cond = '`user_email_address` = "'.$arr_tmp[0].'" AND `user_id` = "'.$arr_tmp[1].'"';
                if($this->Base_model->update($user_password, $cond, 'tz_users'))
                {
                    $message['status'] = 'success';
                    $message['alert'] = 'Password Successfully Modified!';
                }
                else
                {
                    $message['status'] = 'danger';
                    $message['alert'] = 'Failed to reset your password, <br / >Please contact Semicon Event Admin';
                }
            }
            else
            {
                #array form variables need to be declare as array
                $message = array();
                $message['status'] = 'danger';
                $message['alert'] = validation_errors('<span>', '</span>');
            }
        }

        $message['form_url'] = site_url('api/member/resetpassword/'.$email);
        $this->load->view('api/mail_reset_password', $message);
    }

    public function email_get()
    {
        $post_data['user_email_address'] = $this->get('email');

        $this->load->library('email');
        #email reset sending will be place here
        $config['useragent'] = 'Event Admin';
        $config['mailtype'] = 'html';
        $config['charset'] = 'utf-8';
        $config['wordwrap'] = TRUE;
        // $config['send_multipart'] = FALSE;
        $this->email->initialize($config);
        // $this->data['status_message'] = array('Unable to send email', 'warning');
        // support@semicon.tinkermak.com
        $this->email->from('support@semicon.tinkermak.com', '');
        $this->email->to($post_data['user_email_address']);
        // $this->email->cc('another@another-example.com');
        // $this->email->bcc('them@their-example.com');

        // $this->email->subject('Successful Event App Registration');
        // $post_data['encoded_email'] = urlencode(base64_encode($post_data['user_email_address']));
        // $this->email->message($this->load->view('api/mail_register_success', $post_data, true));

        $this->email->subject('Password Reset');
        $post_data['encoded_email'] = urlencode(base64_encode($this->get('email').'|'.$this->get('id')));
        $this->email->message($this->load->view('api/mail_forgot_password', $post_data, true));
        if($this->email->send())
        {
            echo 'mail sent '.$post_data['user_email_address'];
            // setcookie('user_email_address',$post_data['user_email_address'],time()+86400);
            // $this->data['status_message'] = array('Password Reset already sent on your email', 'success');
        }
        else
        {
            echo 'mail failed to send<br>';
            echo $this->email->print_debugger();
        }
    }

    // public function users_get()
    // {
    //     // Users from a data store e.g. database
    //     $users = [
    //         ['id' => 1, 'name' => 'Johntaz', 'email' => 'john@example.com', 'fact' => 'Loves coding'],
    //         ['id' => 2, 'name' => 'Jim', 'email' => 'jim@example.com', 'fact' => 'Developed on CodeIgniter'],
    //         ['id' => 3, 'name' => 'Jane', 'email' => 'jane@example.com', 'fact' => 'Lives in the USA', ['hobbies' => ['guitar', 'cycling']]],
    //     ];

    //     $id = $this->get('id');

    //     // If the id parameter doesn't exist return all the users

    //     if ($id === NULL)
    //     {
    //         // Check if the users data store contains users (in case the database result returns NULL)
    //         if ($users)
    //         {
    //             // Set the response and exit
    //             $this->response($users, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
    //         }
    //         else
    //         {
    //             // Set the response and exit
    //             $this->response([
    //                 'status' => FALSE,
    //                 'message' => 'No users were found'
    //             ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
    //         }
    //     }

    //     // Find and return a single record for a particular user.

    //     $id = (int) $id;

    //     // Validate the id.
    //     if ($id <= 0)
    //     {
    //         // Invalid id, set the response and exit.
    //         $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
    //     }

    //     // Get the user from the array, using the id as key for retrieval.
    //     // Usually a model is to be used for this.

    //     $user = NULL;

    //     if (!empty($users))
    //     {
    //         foreach ($users as $key => $value)
    //         {
    //             if (isset($value['id']) && $value['id'] === $id)
    //             {
    //                 $user = $value;
    //             }
    //         }
    //     }

    //     if (!empty($user))
    //     {
    //         $this->set_response($user, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
    //     }
    //     else
    //     {
    //         $this->set_response([
    //             'status' => FALSE,
    //             'message' => 'User could not be found'
    //         ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
    //     }
    // }

    // public function users_post()
    // {
    //     // $this->some_model->update_user( ... );
    //     $message = [
    //         'id' => 100, // Automatically generated by the model
    //         'name' => $this->post('name'),
    //         'email' => $this->post('email'),
    //         'message' => 'Added a resource'
    //     ];

    //     $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    // }

    // public function users_delete()
    // {
    //     $id = (int) $this->get('id');

    //     // Validate the id.
    //     if ($id <= 0)
    //     {
    //         // Set the response and exit
    //         $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
    //     }

    //     // $this->some_model->delete_something($id);
    //     $message = [
    //         'id' => $id,
    //         'message' => 'Deleted the resource'
    //     ];

    //     $this->set_response($message, REST_Controller::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
    // }
}
