<form id="ajaxForm" class="list-block inputs-list"method="POST">
 <ul>
   <li>
     <div class="item-content">
       <div class="item-inner"> 
         <div class="item-title floating-label">Username</div>
         <div class="item-input">
           <input type="text" name="user_username" />
         </div>
       </div>
     </div>
   </li>
   <li>
     <div class="item-content">
       <div class="item-inner"> 
         <div class="item-title floating-label">Password</div>
         <div class="item-input">
           <input type="password" name="user_password" />
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
    <a id="signin" class="button button-raised button-fill">Login</a>
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
       url: baseUrl+'api/member/login',
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