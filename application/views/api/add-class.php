<form id="ajaxForm" class="list-block inputs-list"method="POST" action="http://localhost/3.1.6/teacher_rating/index.php/api/class/add">
 <ul>
  <li>
     <div class="item-content">
       <div class="item-inner">
         <div class="item-title floating-label">Term</div>
         <div class="item-input">
           <select name="term_id">
            <option value="">Select Term</option>
            <option value="1">Inactive</option>
           </select>
         </div>
       </div>
     </div>
   </li>
  <li>
     <div class="item-content">
       <div class="item-inner">
         <div class="item-title floating-label">Teacher</div>
         <div class="item-input">
           <select name="teacher_id">
            <option value="">Select Teacher</option>
            <option value="1">Inactive</option>
           </select>
         </div>
       </div>
     </div>
   </li>
  <li>
     <div class="item-content">
       <div class="item-inner">
         <div class="item-title floating-label">Subject</div>
         <div class="item-input">
           <select name="subject_id">
            <option value="">Select Subject</option>
            <option value="1">Inactive</option>
           </select>
         </div>
       </div>
     </div>
   </li>
   <li>
     <div class="item-content">
       <div class="item-inner"> 
         <div class="item-title floating-label">class name</div>
         <div class="item-input">
           <input type="text" name="class_name" />
         </div>
       </div>
     </div>
   </li>
   <li>
     <div class="item-content">
       <div class="item-inner"> 
         <div class="item-title floating-label">class start time</div>
         <div class="item-input">
           <input type="text" name="class_start_time" />
         </div>
       </div>
     </div>
   </li>
   <li>
     <div class="item-content">
       <div class="item-inner"> 
         <div class="item-title floating-label">class end time</div>
         <div class="item-input">
           <input type="text" name="class_end_time" />
         </div>
       </div>
     </div>
   </li>
   <li>
     <div class="item-content">
       <div class="item-inner"> 
         <div class="item-title floating-label">class days</div>
         <div class="item-input">
           <input type="text" name="class_days" />
         </div>
       </div>
     </div>
   </li>
 </ul>

 <!-- <input type="submit" value="test"> -->
</form>
<div class="content-block">
 <div class="row">
   <div class="col-100">
    <a id="signin" class="button button-raised button-fill">Add</a>
   </div>
 </div>
</div>
<script type="text/javascript" src="<?php echo base_url('js/jquery.min.js'); ?>"></script>
<script type="text/javascript">
$(function() {   
   var baseUrl = 'http://localhost/3.1.6/teacher_rating/index.php/';

   $('#signin').click(function()
   {
      $.ajax({
       url: baseUrl+'api/class/add',
       type: "POST",
       data: $("#ajaxForm").serialize()
     }).done(function(data)
     {
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
        $("select[name='"+fieldName+"']").attr('style', 'border:1px solid #ff0000;');
        $("select[name='"+fieldName+"']").after(fieldMsg);
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