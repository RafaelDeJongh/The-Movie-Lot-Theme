jQuery(document).ready(function($){
	//ToTop
	$(".site-footer").prepend('<a id="totop" href="#page"></a>');
	var totop = $("#totop");
	$(window).scroll(function(){
		($(this).scrollTop() > 200) ? totop.fadeIn() : totop.fadeOut();
	});
	//Smooth Scroll
	$(function(){
		setTimeout(function(){
		if (location.hash){
			window.scrollTo(0,450);
			target = location.hash.split('#');
			smoothScrollTo($('#'+target[1]));
			}
		},1);
		$('a[href*="#"]').not('[href="#"]').not('[href="#0"]').click(function(event){
			if(location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname){
				var target = $(this.hash);
				target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
				if(target.length){
					event.preventDefault();
					$('html, body').animate({scrollTop: target.offset().top},1000,function(){
						var $target = $(target);
						$target.focus();
						if($target.is(":focus")){
							return false;
						}else{
							$target.attr('tabindex','-1');
							$target.focus();
						};
					});
				}
			}
		});
	});
	$(window).bind("mousewheel",function(){$("html,body").stop(true,false);});
});