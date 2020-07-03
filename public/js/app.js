$(document).ready(function(){
    $("#chronics_number").hide();
    $("#children_number").hide();

    $("#chronics").click(function(){
        $(this).css("background-color");
        if($(this).css("background-color") == "rgb(119, 119, 119)"){
            $("#have_cronics").val(1);
            $("#chronics_number").show();
        }else{
            $("#have_cronics").val(0);
            $("#chronics_number").hide();
        }
    })

    $("#children").click(function(){
        $(this).css("background-color");
        if($(this).css("background-color") == "rgb(119, 119, 119)"){
            $("#have_children").val(1);
            $("#children_number").show();
        }else{
            $("#have_children").val(0);
            $("#children_number").hide();
        }
    })

    var $regexname=/^([a-zA-Z\s]{3,16})$/;
    $('#first_name').on('keypress keydown keyup',function(){
             if ($(this).val() !== "" && !$(this).val().match($regexname)) {
              // there is a mismatch, hence show the error message
                 $('.first_name_emsg').removeClass('ishidden-true');
                 $('.first_name_emsg').addClass('ishidden-false');
                 $('.first_name_emsg').show();
             }
           else{
                // else, do not display message
                $('.first_name_emsg').addClass('ishidden-true');
                 $('.first_name_emsg').removeClass('ishidden-false');
               }
         });
         $('#last_name').on('keypress keydown keyup',function(){
            if ($(this).val() !== "" && !$(this).val().match($regexname)) {
             // there is a mismatch, hence show the error message
                $('.last_name_emsg').removeClass('ishidden-true');
                $('.last_name_emsg').addClass('ishidden-false');
                $('.last_name_emsg').show();
            }
          else{
               // else, do not display message
               $('.last_name_emsg').addClass('ishidden-true');
                $('.last_name_emsg').removeClass('ishidden-false');
              }
        });

        var $regexphohe = /^(([+][(]?[0-9]{1,3}[)]?)|([(]?[0-9]{4}[)]?))\s*[)]?[-\s\.]?[(]?[0-9]{1,3}[)]?([-\s\.]?[0-9]{3})([-\s\.]?[0-9]{3,4})$/im;
        $('#mobile').on('keypress keydown keyup',function(){
            if ($(this).val() !== "" && !$(this).val().match($regexphohe)) {
             // there is a mismatch, hence show the error message
                $('.mobile_emsg').removeClass('ishidden-true');
                $('.mobile_emsg').addClass('ishidden-false');
                $('.mobile_emsg').show();
            }
          else{
               // else, do not display message
               $('.mobile_emsg').addClass('ishidden-true');
                $('.mobile_emsg').removeClass('ishidden-false');
              }
        });
})