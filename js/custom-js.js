jQuery(document).ready(function(){
    jQuery('#duplicar-colaborador-curso-custom').click(function(event){                                
        jQuery('#elements:first').clone(true).appendTo('#receive').find("input[type='text']").val("");                
        event.preventDefault();
    });
});