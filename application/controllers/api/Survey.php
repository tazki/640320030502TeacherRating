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
class Survey extends REST_Controller {

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
        $search_query = '`survey_trashed_at` IS NULL AND `survey_status` = 2';        
        $data['rows'] = $this->Base_model->list_all('tz_survey', '', '', 'survey_sort', 'asc', $search_query);
        $this->set_response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
    }

    public function rate_post()
    {
        // tz_rating_to_teacher
            // rating_to_teacher_id (PK)
            // class_id
            // student_id
            // rating_comment
            // rating_created_by
            // rating_created_at
            // rating_modified_by
            // rating_modified_at
            // rating_trashed_by
            // rating_trashed_at
            // rating_deleted_by
            // rating_deleted_at
        // tz_rating_to_survey
            // survey_id
            // survey_rating
            // rating_to_teacher_id
            // rating_to_survey_id (PK)        
        $post_data = $this->input->post(null, false);
        if(sizeof($_POST) > 0)
        {
            $post_data['rating_created_at'] = datenow();
            $post_data['rating_modified_at'] = datenow();
            $rating_to_teacher_id = $this->Base_model->insert($post_data, 'tz_rating_to_teacher');
            if(!empty($rating_to_teacher_id))
            {
                $clean_data = array();
                foreach($post_data as $key => $survey_rating)
                {
                    if(stristr($key, 'survey_id_'))
                    {
                        $survey_id = str_replace('survey_id_', '', $key);
                        $clean_data[$key]['survey_id'] = $survey_id;
                        $clean_data[$key]['survey_rating'] = $survey_rating;
                        $clean_data[$key]['rating_to_teacher_id'] = $rating_to_teacher_id;
                    }
                }

                foreach($clean_data as $key => $val)
                {
                    $this->Base_model->insert($val, 'tz_rating_to_survey');
                }

                $message['rating_to_teacher_id'] = $rating_to_teacher_id;
                $message['status'] = 'success';
                $message['alert'] = 'Survey Successfully Saved';
            }
            else
            {
                $message['status'] = 'danger';
                $message['alert'] = 'Survey Failed to Save';
            }
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function index_get()
    {
        $search_query = '`survey_trashed_at` IS NULL';
        
        $search = $this->get('q', TRUE);        
        if(!empty($search))
        {
            $search_query .= ' AND (`survey_question` LIKE "%'.$search.'%")';
        }
        $total_segments = $this->uri->total_segments();
        $total_count = $this->Base_model->count('tz_survey', $search_query);
        $this->data['total_count'] = $total_count;
        $pagination = paginationForApi($this->controller, $total_count, $total_segments, $this->input->get('per_page', TRUE));
        $sort = $this->input->get('sort', TRUE);
        $sort = (!empty($sort)) ? $sort : 'survey_sort';
        $order = $this->input->get('order', TRUE);
        $order = (!empty($order)) ? $order : 'asc';
        $rows = $this->Base_model->list_all('tz_survey', $pagination['per_page'], $this->uri->segment($total_segments), $sort, $order, $search_query);
        if(is_array($rows))
        {
            foreach($rows as $key => $val)
            {
                $data['rows'][$key] = $val;
                $data['rows'][$key]['survey_created_at'] = dateformat($val['survey_created_at'], 'Y/m/d');
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
            $row = $this->Base_model->list_all('tz_survey', '', '', '', '', 'survey_id="'.$id.'"');
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
                    'field'   => 'survey_question',
                    'label'   => 'Question',
                    'rules'   => 'trim|required'
                )
            );
            $this->form_validation->set_rules($this->config_data);
            if($this->form_validation->run() == true)
            {
                if(!empty($id) && is_numeric($id))
                {
                    $cond = array('survey_id' => $id);
                    $post_data['survey_modified_at'] = datenow();
                    $row = $this->Base_model->update($post_data, $cond, 'tz_survey');
                    if(isset($row['survey_id']) && !empty($row['survey_id']))
                    {
                        $message['survey_id'] = $row['survey_id'];
                        $message['status'] = 'success';
                        $message['alert'] = 'Survey Successfully Saved';
                    }
                    else
                    {
                        $message['status'] = 'danger';
                        $message['alert'] = 'Survey Failed to Save';
                    }
                }
                else
                {
                    // $post_data['survey_status'] = 2;
                    $post_data['survey_created_at'] = datenow();
                    $post_data['survey_modified_at'] = datenow();
                    $id = $this->Base_model->insert($post_data, 'tz_survey');
                    if(!empty($id))
                    {
                        $message['survey_id'] = $id;
                        $message['status'] = 'success';
                        $message['alert'] = 'Survey Successfully Saved';
                    }
                    else
                    {
                        $message['status'] = 'danger';
                        $message['alert'] = 'Survey Failed to Save';
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
            $cond = array('survey_id' => $id);
            $post_data['survey_trashed_at'] = datenow();
            $id = $this->Base_model->update($post_data, $cond, 'tz_survey');
            if(!empty($id))
            {
                $message['status'] = 'success';
                $message['alert'] = 'Survey Successfully Deleted';
            }
            else
            {
                $message['status'] = 'danger';
                $message['alert'] = 'Survey Failed to Save';
            }
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
}
