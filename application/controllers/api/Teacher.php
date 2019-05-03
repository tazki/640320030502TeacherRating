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
class Teacher extends REST_Controller {

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

    public function weblist_get()
    {
        $search_query = '`user_trashed_at` IS NULL AND `user_group_id` = 6 AND `user_current_status_id` = 2';        
        $rows = $this->Base_model->list_all('tz_users', '', '', 'user_first_name', 'asc', $search_query);
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

                if(isset($prep_rating_to_teacher) && is_array($prep_rating_to_teacher))
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
                        $data['rows'][$teacher_id]['total_average'] = round($total_average,2);
                    }
                }
                // prearr($data['rows']);
            }
            #EOS total teacher rating
        }
        $this->set_response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
    }

    public function list_get()
    {
        $search_query = '`user_trashed_at` IS NULL AND `user_group_id` = 6 AND `user_current_status_id` = 2';        
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
                    #remove existing image if user only click remove button
                    if(isset($post_data['event_image_is_remove']) && $post_data['event_image_is_remove'] == 'yes'
                        && !empty($post_data['user_avatar']))
                    {
                        #remove old image to save server disk space
                        imageremove($post_data['user_avatar'], 'uploads');
                        $post_data['user_avatar'] = '';
                    }

                    if(empty($_FILES['event_image_uploader']['error']))
                    {
                        $image_filename = imageupload('event_image_uploader', $post_data['user_avatar']);
                        $post_data['user_avatar'] = str_replace('senta/', '', base_url('uploads')).'/'.$image_filename;
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
                    
                    $cond = array('user_id' => $id);
                    $post_data['user_modified_at'] = datenow();
                    $row = $this->Base_model->update($post_data, $cond, 'tz_users');
                    if(isset($row['user_id']) && !empty($row['user_id']))
                    {
                        $message['user_id'] = $row['user_id'];
                        $message['status'] = 'success';
                        $message['alert'] = 'Teacher Successfully Saved';
                    }
                    else
                    {
                        $message['status'] = 'danger';
                        $message['alert'] = 'Teacher Failed to Save';
                    }
                }
                else
                {
                    #remove existing image if user only click remove button
                    if(empty($_FILES['event_image_uploader']['error']))
                    {
                        $image_filename = imageupload('event_image_uploader', $post_data['user_avatar']);
                        $post_data['user_avatar'] = str_replace('senta/', '', base_url('uploads')).'/'.$image_filename;
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

                    $post_data['user_group_id'] = 6;
                    // $post_data['user_current_status_id'] = 2;
                    $post_data['user_created_at'] = datenow();
                    $post_data['user_modified_at'] = datenow();
                    $id = $this->Base_model->insert($post_data, 'tz_users');
                    if(!empty($id))
                    {
                        $message['user_id'] = $id;
                        $message['status'] = 'success';
                        $message['alert'] = 'Teacher Successfully Saved';
                    }
                    else
                    {
                        $message['status'] = 'danger';
                        $message['alert'] = 'Teacher Failed to Save';
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
                        $message['alert'][$field_name] = strip_tags($error_msg);
                    }
                }
            }
        }

        // $message['file'] = $_FILES;
        // $message['uploads'] = base_url('uploads');
        // prearr($message);
        // $test = urlencode(base64_encode(json_encode($message)));
        // echo json_encode($message);
        // prearr(json_decode(base64_decode(urldecode($test)), true));

        $form_origin_url = '';#put actual url here
        if(isset($post_data['form_origin_url']))
        {
            $form_origin_url = $post_data['form_origin_url'].'&msg='.urlencode(base64_encode(json_encode($message)));
        }
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
            $cond = array('user_id' => $id);
            $post_data['user_trashed_at'] = datenow();
            $id = $this->Base_model->update($post_data, $cond, 'tz_users');
            if(!empty($id))
            {
                $message['status'] = 'success';
                $message['alert'] = 'Teacher Successfully Deleted';
            }
            else
            {
                $message['status'] = 'danger';
                $message['alert'] = 'Teacher Failed to Save';
            }
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function clearrating_get()
    {
        $id = $this->get('id');
        // Validate the id.
        if ($id <= 0)
        {
            // Set the response and exit
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }
        else
        {
            $search_query = '`teacher_id` = "'.$id.'"';
            $rows = $this->Base_model->list_all('tz_rating_to_teacher', '', '', '', '', $search_query, 'rating_to_teacher_id');
            if(is_array($rows))
            {
                $rating_to_teacher_id_list = '';
                foreach($rows as $val)
                {
                    $comma = (!empty($rating_to_teacher_id_list)) ? ',' : '';
                    $rating_to_teacher_id_list .= $comma.$val['rating_to_teacher_id'];
                }

                $cond = array('teacher_id' => $id);
                $this->Base_model->delete($cond, 'tz_rating_to_teacher');

                $cond = '`rating_to_teacher_id` IN('.$rating_to_teacher_id_list.')';
                $id = $this->Base_model->delete($cond, 'tz_rating_to_survey');
                if(!empty($id))
                {
                    $message['status'] = 'success';
                    $message['alert'] = 'Teacher Rating Successfully Deleted';
                }
                else
                {
                    $message['status'] = 'danger';
                    $message['alert'] = 'Failed to clear rating';
                }
            }
            else
            {
                $message['status'] = 'danger';
                $message['alert'] = 'Teacher Rating not found.';
            }
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
}
