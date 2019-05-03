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
class Becomeastudent extends REST_Controller {

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

    public function index_get()
    {
        $search_query = '`trashed_at` IS NULL';
        
        $search = $this->get('q', TRUE);        
        if(!empty($search))
        {
            $search_query .= ' AND (`student_name` LIKE "%'.$search.'%")';
        }
        $total_segments = $this->uri->total_segments();
        $total_count = $this->Base_model->count('tz_become_a_student', $search_query);
        $this->data['total_count'] = $total_count;
        $pagination = paginationForApi($this->controller, $total_count, $total_segments, $this->input->get('per_page', TRUE));
        $sort = $this->input->get('sort', TRUE);
        $sort = (!empty($sort)) ? $sort : 'created_at';
        $order = $this->input->get('order', TRUE);
        $rows = $this->Base_model->list_all('tz_become_a_student', $pagination['per_page'], $this->uri->segment($total_segments), $sort, $order, $search_query);
        if(is_array($rows))
        {
            foreach($rows as $key => $val)
            {
                $data['rows'][$key] = $val;
                $data['rows'][$key]['created_at'] = dateformat($val['created_at'], 'Y/m/d');
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
            $row = $this->Base_model->list_all('tz_become_a_student', '', '', '', '', 'become_a_student_id="'.$id.'"');
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

        if(!isset($post_data['accept-this-1']))
        {
            $post_data['accept-this-1'] = '';
        }

        if(sizeof($_POST) > 0)
        {
            $message['status'] = 'danger';
            $message['alert'] = 'Fill all the fields.';

            $this->config_data = array(
                array(
                    'field'   => 'student_name',
                    'label'   => 'Student Name',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'student_birthday',
                    'label'   => 'Date of Birth',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'student_age',
                    'label'   => 'Age',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'student_birth_place',
                    'label'   => 'Place of Birth',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'student_mobile_number',
                    'label'   => 'Mobile Number',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'student_address',
                    'label'   => 'Address',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'student_email_address',
                    'label'   => 'Email Address',
                    'rules'   => 'trim|required'
                ),
                array(
                    'field'   => 'accept-this-1',
                    'label'   => 'Terms & Condition',
                    'rules'   => 'trim|required'
                )
            );
            $this->form_validation->set_rules($this->config_data);
            if($this->form_validation->run() == true)
            {
                if(!empty($id) && is_numeric($id))
                {
                    #remove existing image if user only click remove button
                    // if(isset($post_data['event_image_is_remove']) && $post_data['event_image_is_remove'] == 'yes'
                    //     && !empty($post_data['user_avatar']))
                    // {
                    //     #remove old image to save server disk space
                    //     imageremove($post_data['user_avatar'], 'uploads');
                    //     $post_data['user_avatar'] = '';
                    // }

                    // if(empty($_FILES['event_image_uploader']['error']))
                    // {
                    //     $image_filename = imageupload('event_image_uploader', $post_data['user_avatar']);
                    //     $post_data['user_avatar'] = str_replace('senta/', '', base_url('uploads')).'/'.$image_filename;
                    //     if(empty($image_filename))
                    //     {
                    //         //$this->data['status_message'] = alert('danger', $this->data['uploader_error']);
                    //         echo $this->data['uploader_error'];
                    //         die;
                    //     }
                    // }
                    // else
                    // {
                    //     unset($post_data['event_image_uploader']);
                    // }

                    $cond = array('become_a_student_id' => $id);
                    $post_data['modified_at'] = datenow();
                    $row = $this->Base_model->update($post_data, $cond, 'tz_become_a_student');
                    if(isset($row['become_a_student_id']) && !empty($row['become_a_student_id']))
                    {
                        $message['become_a_student_id'] = $row['become_a_student_id'];
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
                    #remove existing image if user only click remove button
                    if(empty($_FILES['event_image_uploader']['error']))
                    {
                        $image_filename = imageupload('event_image_uploader', $post_data['student_avatar']);
                        $post_data['student_avatar'] = str_replace('senta/', '', base_url('uploads')).'/'.$image_filename;
                        if(empty($image_filename))
                        {
                            //$this->data['status_message'] = alert('danger', $this->data['uploader_error']);
                            echo $this->data['uploader_error'];
                            die;
                        }
                    }
                    else
                    {
                        unset($post_data['event_image_uploader']);
                    }

                    $post_data['created_at'] = datenow();
                    $post_data['modified_at'] = datenow();
                    $id = $this->Base_model->insert($post_data, 'tz_become_a_student');
                    if(!empty($id))
                    {
                        $message['become_a_student_id'] = $id;
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

        // prearr($_FILES);
        // prearr($post_data);
        $message['post_data'] = $post_data;
        $form_origin_url = '';#put actual url here
        if(isset($post_data['form_origin_url']))
        {
            // $form_origin_url = $post_data['form_origin_url'].'?msg='.urlencode(base64_encode(json_encode($message)));
            $form_origin_url = $post_data['form_origin_url'].'?msg='.urlencode(json_encode($message));
        }
        // prearr($message);
        // echo $form_origin_url;
        redirect($form_origin_url);
        // $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
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
            $cond = array('become_a_student_id' => $id);
            $post_data['trashed_at'] = datenow();
            $id = $this->Base_model->update($post_data, $cond, 'tz_become_a_student');
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
