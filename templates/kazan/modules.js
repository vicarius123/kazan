
jQuery(document).ready(function(){
	
	
	jQuery('.panel').click(function(){
		jQuery('.panel').find('.panel-heading').removeClass('activefaq');
		var acc = jQuery('.collapse');
		if(acc.hasClass('in')){
			jQuery(this).find('.panel-heading').addClass('activefaq');
			
			console.log(jQuery(this).prev());
		}
	});
	jQuery('.faq li').click(function(e){
		e.preventDefault();
		
		//Expand or collapse this panel
		jQuery(this).find('.slideup').slideToggle('500');
		jQuery(this).find('h4').addClass('activefaq');
		
		//Hide the other panels
		jQuery(".faq li h4").not(jQuery(this).find('h4')).removeClass('activefaq');
		jQuery(".faq li .slideup").not(jQuery(this).find('.slideup')).slideUp('500');

		
	});
	
	jQuery('.ic-unsee').click(function(){
		
		jQuery('.more_op').fadeToggle();
	});
	
	jQuery('.close_b').click(function(){
		jQuery('.more_op').fadeToggle();
		jQuery('.sheet *:not("ymaps,canvas")').removeAttr('style');
		jQuery('.footer *').removeAttr('style');
		jQuery('.footer').removeAttr('style');
		
		jQuery('.sheet div:not(".request, .request .wrap"),.sheet').removeAttr('style');


		jQuery('body').css('transform','scale(1)');
		
		document.cookie = "zoom=0";
		document.cookie = "blind=0";
	});
	var cook = document.cookie;
	
	var cookies = cook.split('; ');
	
	console.log(cookies);
	
	jQuery(cookies).each(function(index, value){
		console.log(value);
		
		if(value == 'zoom=1'){
			jQuery('body').css('transform','scale(1.1)');
		}
		if(value == 'zoom=3'){
			jQuery('body').css('transform','scale(1.3)');
		}
		if(value == 'zoom=5'){
			jQuery('body').css('transform','scale(1.5)');
		}
		
		if(value == 'blind=1'){
			jQuery('.sheet *').css({'color':'#000'});
			jQuery('.footer *').css({'color':'#000','background':'#fff'});
			jQuery('.footer').css({'color':'#000','background':'#fff'});
			jQuery('.sheet div:not(".request, .request .wrap"),.sheet').css({'background':'#fff'});
		}
		if(value == 'blind=2'){
			jQuery('.sheet *').css({'color':'#fff'});
			jQuery('.footer *').css({'color':'#fff','background':'#000'});
			jQuery('.footer').css({'color':'#fff','background':'#000'});
			jQuery('.sheet div:not(".request, .request .wrap"),.sheet').css({'background':'#000'});
		}
		if(value == 'blind=3'){
			jQuery('.sheet *').css({'color':'#000'});
			jQuery('.footer *').css({'color':'#000','background':'#077D3F'});
			jQuery('.footer').css({'color':'#000','background':'#077D3F'});
			jQuery('.sheet div:not(".request, .request .wrap"),.sheet').css({'background':'#077D3F'});
		}
		
		if(value == 'blind=1' || value == 'zoom=3' || value == 'zoom=5' || value == 'blind=1' || value == 'blind=2' || value == 'blind=3'){
			jQuery('.more_op').show();
		}
	});
	jQuery('.big').click(function(){
		jQuery('body').css('transform','scale(1.1)');
		document.cookie = "zoom=1";
	});
	console.log(cook);
	jQuery('.bigger').click(function(){
		jQuery('body').css('transform','scale(1.3)');
		document.cookie = "zoom=3";
	});
	jQuery('.biggest').click(function(){
		jQuery('body').css('transform','scale(1.5)');
		document.cookie = "zoom=5";
	});
	
	jQuery('.white_c').click(function(){
		jQuery('.sheet *').css({'color':'#000'});
		jQuery('.footer *').css({'color':'#000','background':'#fff'});
		jQuery('.footer').css({'color':'#000','background':'#fff'});
		jQuery('.sheet div,.sheet').css({'background':'#fff'});
		
		document.cookie = "blind=1";
	});
	
	jQuery('.black_c').click(function(){
		jQuery('.sheet *').css({'color':'#fff'});
		jQuery('.footer *').css({'color':'#fff','background':'#000'});
		jQuery('.footer').css({'color':'#fff','background':'#000'});
		jQuery('.sheet div,.sheet').css({'background':'#000'});
		document.cookie = "blind=2";
	});
	
	jQuery('.blue_c').click(function(){
		jQuery('.sheet *').css({'color':'#000'});
		jQuery('.footer *').css({'color':'#000','background':'#077D3F'});
		jQuery('.footer').css({'color':'#000','background':'#077D3F'});
		jQuery('.sheet div,.sheet').css({'background':'#077D3F'});
		document.cookie = "blind=3";
	});
});

