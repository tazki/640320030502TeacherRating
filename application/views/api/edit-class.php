<form id="ajaxForm" class="list-block inputs-list"method="POST">
 <ul>
   <li>
     <div class="item-content">
       <div class="item-inner"> 
         <div class="item-title floating-label">class name</div>
         <div class="item-input">
           <input type="text" name="class_name" id="class_name" />
         </div>
       </div>
     </div>
   </li>
   <li>
     <div class="item-content">
       <div class="item-inner">
         <div class="item-title floating-label">status</div>
         <div class="item-input">
           <select name="class_status">
            <option value="2">Active</option>
            <option value="1">Inactive</option>
           </select>
         </div>
       </div>
     </div>
   </li>
 </ul>
</form>
<div class="content-block">
 <div class="row">
   <div class="col-100">
    <a id="signin" class="button button-raised button-fill">Update</a>
   </div>
 </div>
</div>
<script type="text/javascript" src="<?php echo base_url('js/jquery.min.js'); ?>"></script>
<script type="text/javascript">
$(function() {   
   var baseUrl = 'http://localhost/3.1.6/teacher_rating/index.php/';

   $.ajax({
     url: baseUrl+'api/class/detail/1',
     type: "GET"
   }).done(function(data)
   {
    console.log(data);
    $('#term_name').val(data.term_name);
   });

   $('#signin').click(function()
   {
      $.ajax({
       url: baseUrl+'api/class/edit/1',
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