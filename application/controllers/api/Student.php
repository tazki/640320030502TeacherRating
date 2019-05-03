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
class Student extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->controller = strtolower(get_class($this));
        $this->data['controller'] = $this->controller;
        $this->data['uri_string'] = $this->uri->uri_string();
        $this->data['get_query'] = $this->input->get(NULL, TRUE);
        $this->load->model('Base_model');
    }

    public function list_get()
    {
        $search_query = '`user_trashed_at` IS NULL AND `user_group_id` = 7 AND `user_current_status_id` = 2';        
        $data['rows'] = $this->Base_model->list_all('tz_users', '', '', 'user_first_name', 'asc', $search_query);
        $this->set_response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
    }

    public function edit_post()
    {
        $id = $this->get('id');

        $this->load->library('form_validation');
        $post_data = $this->input->post(null, false);
        if(sizeof($_POST) > 0)
        {
            $message['status'] = 'danger';
            $message['alert'] = 'Fill all the fields.';

            $this->config_data = array(
                array(
                    'field'   => 'section_id',
                    'label'   => 'Section',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'user_first_name',
                    'label'   => 'First Name',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'user_last_name',
                    'label'   => 'Last Name',
                    'rules'   => 'trim|required'
                )
            );
            $this->form_validation->set_rules($this->config_data);
            if($this->form_validation->run() == true)
            {
                if(!empty($id) && is_numeric($id))
                {
                    $cond = array('user_id' => $id);
                    $post_data['user_modified_at'] = datenow();
                    $row = $this->Base_model->update($post_data, $cond, 'tz_users');
                    if(isset($row['user_id']) && !empty($row['user_id']))
                    {
                        $cond = array('student_id' => $row['user_id']);
                        $id = $this->Base_model->update($post_data, $cond, 'tz_section_to_student');

                        $message['user_id'] = $row['user_id'];
                        $message['status'] = 'success';
                        $message['alert'] = 'Data Successfully Saved';
                    }
                    else
                    {
                        $message['status'] = 'danger';
                        $message['alert'] = 'Data Failed to Save';
                    }
                }
                else
                {
                    $post_data['user_group_id'] = 7;
                    // $post_data['user_current_status_id'] = 2;
                    $post_data['user_created_at'] = datenow();
                    $post_data['user_modified_at'] = datenow();
                    $id = $this->Base_model->insert($post_data, 'tz_users');
                    if(!empty($id))
                    {
                        $post_data['student_id'] = $id;
                        $id = $this->Base_model->insert($post_data, 'tz_section_to_student');

                        $message['user_id'] = $id;
                        $message['status'] = 'success';
                        $message['alert'] = 'Data Successfully Saved';
                    }
                    else
                    {
                        $message['status'] = 'danger';
                        $message['alert'] = 'Data Failed to Save';
                    }
                }
            }
            else
            {
                #array form variables need to be declare as array
                $message = array();
                $message['status'] = 'danger';
                // $message['alert'] = validation_errors('<span>', '</span>');
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

    public function delete_get()
    {
        $id = (int) $this->get('id');

        // Validate the id.
        if ($id <= 0)
        {
            // Set the response and exit
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }
        else
        {
            $cond = array('user_id' => $id);
            $post_data['user_trashed_at'] = datenow();
            $id = $this->Base_model->update($post_data, $cond, 'tz_users');
            if(!empty($id))
            {
                $message['status'] = 'success';
                $message['alert'] = 'Data Successfully Deleted';
            }
            else
            {
                $message['status'] = 'danger';
                $message['alert'] = 'Data Failed to Save';
            }
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
}
