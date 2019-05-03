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
class Subject extends REST_Controller {

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
        $search_query = '`subject_trashed_at` IS NULL AND `subject_status` = 2';        
        $data['rows'] = $this->Base_model->list_all('tz_subject', '', '', 'subject_name', 'asc', $search_query);
        $this->set_response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
    }

    public function index_get()
    {
        $search_query = '`subject_trashed_at` IS NULL';
        
        $search = $this->get('q', TRUE);        
        if(!empty($search))
        {
            $search_query .= ' AND (`subject_name` LIKE "%'.$search.'%")';
        }
        $total_segments = $this->uri->total_segments();
        $total_count = $this->Base_model->count('tz_subject', $search_query);
        $this->data['total_count'] = $total_count;
        $pagination = paginationForApi($this->controller, $total_count, $total_segments, $this->input->get('per_page', TRUE));
        $sort = $this->input->get('sort', TRUE);
        $sort = (!empty($sort)) ? $sort : 'subject_created_at';
        $order = $this->input->get('order', TRUE);
        $rows = $this->Base_model->list_all('tz_subject', $pagination['per_page'], $this->uri->segment($total_segments), $sort, $order, $search_query);
        if(is_array($rows))
        {
            foreach($rows as $key => $val)
            {
                $data['rows'][$key] = $val;
                $data['rows'][$key]['subject_created_at'] = dateformat($val['subject_created_at'], 'Y/m/d');
            }
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
            $row = $this->Base_model->list_all('tz_subject', '', '', '', '', 'subject_id="'.$id.'"');
            if(is_array($row[0]))
            {
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
                    'field'   => 'subject_name',
                    'label'   => 'Subject Name',
                    'rules'   => 'trim|required'
                )
            );
            $this->form_validation->set_rules($this->config_data);
            if($this->form_validation->run() == true)
            {
                if(!empty($id) && is_numeric($id))
                {
                    $cond = array('subject_id' => $id);
                    $post_data['subject_modified_at'] = datenow();
                    $row = $this->Base_model->update($post_data, $cond, 'tz_subject');
                    if(isset($row['subject_id']) && !empty($row['subject_id']))
                    {
                        $message['subject_id'] = $row['subject_id'];
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
                    // $post_data['subject_group_id'] = 6;
                    // $post_data['subject_current_status_id'] = 2;
                    $post_data['subject_created_at'] = datenow();
                    $post_data['subject_modified_at'] = datenow();
                    $id = $this->Base_model->insert($post_data, 'tz_subject');
                    if(!empty($id))
                    {
                        $message['subject_id'] = $id;
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
            $cond = array('subject_id' => $id);
            $post_data['subject_trashed_at'] = datenow();
            $id = $this->Base_model->update($post_data, $cond, 'tz_subject');
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
