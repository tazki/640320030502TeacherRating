<form id="ajaxForm" class="list-block inputs-list"method="POST" action="http://localhost/3.1.6/teacher_rating/index.php/api/survey/rate">
 <ul class="survey-holder"></ul>

  class_id:<input type="text" name="class_id" value="3" /><br>
  teacher_id:<input type="text" name="teacher_id" value="1" /><br>
  student_id:<input type="text" name="student_id" value="4" />
  <input type="submit" value="Debug" />
</form>
<div class="content-block">
 <div class="row">
   <div class="col-100">
    <br><br>
    <a id="rate" class="button button-raised button-fill">Rate</a>    
   </div>
 </div>
</div>
<script type="text/javascript" src="<?php echo base_url('js/jquery.min.js'); ?>"></script>
<script type="text/javascript">
$(function() {   
   var baseUrl = 'http://localhost/3.1.6/teacher_rating/index.php/';

   $.ajax({
     url: baseUrl+'api/survey/list',
     type: "GET"
   }).done(function(data)
   {
    $.each(data.rows, function(key, val)
    {
      var item = '<li>'+
         '<div class="item-content">'+
           '<div class="item-inner">'+
             '<div class="item-title floating-label">'+val.survey_question+'</div>'+
             '<div class="item-input">'+
              '<input type="text" name="survey_id_'+val.survey_id+'" value="1" />'+
             '</div>'+
           '</div>'+
         '</div>'+
       '</li>';

      $('.survey-holder').append(item);
    });
   });

   $('#rate').click(function()
   {
      $.ajax({
       url: baseUrl+'api/survey/rate',
       type: "POST",
       data: $("#ajaxForm").serialize()
     }).done(function(data)
     {
      console.log(data);
      console.log(data.status + ' ' + data.alert + ' ' + $.type(data.alert));

      $("#ajaxForm span.error").remove();
      $("#ajaxForm div.msg-holder").remove();
      if(data.status == 'danger' && $.type(data.alert) == 'object')
      {
       $.each(data.alert, function(fieldName, fieldMsg)
       {
        console.log('key:'+fieldName+' value:'+fieldMsg);
        $("input[name='"+fieldName+"']").attr('style', 'border:1px solid #ff0000;');
        $("input[name='"+fieldName+"']").after(fieldMsg);
       });   
      }
      else
      {
        $('#ajaxForm').prepend('<div class="msg-holder '+data.status+'">'+data.alert+'</div>');
      }
     });
  });
});
</script>