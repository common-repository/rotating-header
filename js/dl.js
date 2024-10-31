if (window.jQuery) {
(function($) {
	$.fn.dl = function(options) {
	  return this.each(function() {
			var selected_tab = $(".dots li:first", this);
			var selected_index = 0;
			var num_slides = $(".dots li").length;
			var img_width = $("img", this).width();//906;
      var img_height = $("img", this).height();
			var timeout_holder = null;

      var data = {rt:5000,td:250};
      try { data = $.parseJSON($(this).attr("data")); } catch(e) { console.error(e); }
      if (!data) { data = {rt:5000,td:250}; }
      if (!data.rt) {
        data.rt = 5000;
      }
      if (!data.td) {
        data.td = 250;
      }
      //console.log(data);

			var rotation_timeout = parseInt(data.rt);
			var transition_duration = parseInt(data.td);
      if (rotation_timeout == 0) { rotation_timeout = 5000; }
      if (transition_duration == 0) { transition_duration = 250; }
			var is_rotating = true;
	    var current_offset = 0;
	    var is_moving = false;

      // maintain an array of the slides
      var slideBoxPositions = [];

      if (options && options.reset) {
        //console.log("call setTabIndex: 0");
        setTabIndex(0);
        num_slides = $(".dots li").length;
        //console.log("num_slides:" + num_slides);
        return;
      }

			// rotates the slides ?>
		  function rotate(delay) {
        //console.log("rotate: " + delay);
		    if (!timeout_holder) {				      
			    timeout_holder = setTimeout(function () { 
			      linear_rotate();
			      //moveToSlide((selected_index + 1 < num_slides) ? selected_index + 1 : 0);
			    }, delay);
		    }
		  }
      function setTabIndex(tabIndex) {
        //console.log("li:nth-child(" + tabIndex + ")");
        selected_tab.removeClass("selected");
        selected_tab = $(".dots li:nth-child("+(tabIndex)+")");
        selected_tab.addClass("selected");
      }
      
		  function linear_rotate() {

		    //stub this out
        // previous_index = slideIndex;
        //        selected_index = slideIndex;
        current_offset++;
        setTabIndex(current_offset%num_slides + 1);
        selected_index = current_offset%num_slides;
        //console.log("new selected_index: " + selected_index);
        is_moving = true;
        $(".slides").animate({"left": (-img_width)+"px", "easing":"strongeaseout"}, transition_duration, function()		 {  
          is_moving = false;
          if (is_rotating) {
            var shift_img = ($(".slides li:nth-child(1)"));
            shift_img.detach();
            $(".slides").append(shift_img);
            $(".slides").css("left", "0px")
            if (timeout_holder) clearTimeout(timeout_holder);
        		timeout_holder = null;
            rotate(rotation_timeout);
          }
        });
		  }
		  
      //
      // move to slideIndex from selected_index
      // always shifting the slides to the left from the right
      //
		  function moveToSlide(slideIndex) {
        //console.log("moveToSlide: " + slideIndex + ", " + (slideIndex+1) );
	    	setTabIndex(slideIndex + 1);
				selected_index = slideIndex;
	
				is_moving = true;
        //console.log("animate: " + (-selected_index*img_width) + "px");
				$(".slides").animate({"left": (-selected_index*img_width)+"px", "easing":"strongeaseout"}, transition_duration, function() {
				  is_moving = false;
				  if (is_rotating) {
            if (timeout_holder) clearTimeout(timeout_holder);
						timeout_holder = null;
				    rotate(rotation_timeout);
				  }
				});
		  }
		  
		  function resetSlides() {
		    var offset = current_offset%num_slides;
		    for(var i = 0; i < offset; i++) {
		      var cur_slide = $("#dl-wrapper li").last();
		      cur_slide.detach();
		      $("#dl-wrapper ul").prepend(cur_slide);

		    }
	      $("#dl-wrapper ul").css("left", (-offset * img_width)+"px");
		    
		  }
		  
		  $(".dots li a", this).click(function(e) {
				e.preventDefault();
	
				if (!is_moving) {
				  var current_tab_index = $(".dots li a").index($(this));
  				if (is_rotating) {
  				  //called to reorder the slides
  				  resetSlides();
  				}
  				is_rotating = false;
  				if (timeout_holder) clearTimeout(timeout_holder);
  				timeout_holder = null;
  				if (selected_index != current_tab_index) {
            //console.log("clicked on " + selected_index);
  				  moveToSlide(current_tab_index);
  				}
  				
				}
			});

      if (num_slides > 1) {
        //Start rotating
        rotate(rotation_timeout);
      }
	  });
	};
	
})(jQuery);
}
