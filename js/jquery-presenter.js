(function($) {
  if (!Function.prototype.bind) {
    // bind from prototype.js essential for classes
    (function() { var slice = Array.prototype.slice; function update(array, args) { var arrayLength = array.length, length = args.length; while (length--) array[arrayLength + length] = args[length]; return array; }; function merge(array, args) { array = slice.call(array, 0); return update(array, args); } ; Function.prototype.bind = function(context) { if (arguments.length < 2 && arguments[0] == undefined) return this; var __method = this, args = slice.call(arguments, 1); return function() { var a = merge(args, arguments); return __method.apply(context, a); } } ; Function.prototype.bindEV = function(context) { var __method = this, args = slice.call(arguments, 1); return function(event) { var a = update([event || window.event], args); return __method.apply(context, a); } }; })();
  }

  // rotation class
  function Banner(el, opts) {this.init(el, opts)}
  Banner.prototype = {
    init: function(el, opts) {
      this.element = $(el);
      this.options = opts;
      this.banner_slides_class = this.options.banner_slides_class;
      this.length  = $("ul" + this.banner_slides_class + " li", this.element).length;
      this.width   = $("ul" + this.banner_slides_class + " li img", this.element).width();
      this.current_index = 0;
      this._stop = false;
      this.setup();
      this.jqv = parseFloat(jQuery.fn.jquery.replace(/\./,''));

      this.tag = 'img';

      if (this.options.height) {
        var next = $($("ul.slides li", this.element)[0]);
        this.shiftImage(next);
      }
    },
    // the strategy will be to have each li position absolute relative to the ul.banners
    // this way as we rotate to the left from the right we'll be able to reposition the first
    // li to the right making the whole process appear seemless
    setup: function() {
      // determine current selected
      var selected = 0;
      $("ul.dots li", this.element).each(function() {
        if ($(this).hasClass("selected")) { return false; }
        selected++;
      });
      this.current_index = selected;
      // remove any padding or margin
      $("ul" + this.banner_slides_class + " li", this.element).css({margin:0,padding:0,opacity:0,visibility:'hidden',float:'none'});
      $("ul" + this.banner_slides_class, this.element).css({position:'relative'});
      $("ul" + this.banner_slides_class + " li:nth-child(" + (this.current_index+1) + ")", this.element).css({opacity:1,visibility:'visible'});

      // hide all banners
      //$("ul.banners li", this.element).hide();
      // show the selected
      //$("ul.banners li:nth-child(" + (selected+1) + ")", this.element).show();
 
      // update banners to li.length * li img.width
      $("ul" + this.banner_slides_class , this.element).width(this.length * $("ul" + this.banner_slides_class + " li " + this.tag, this.element).width());
      //$("ul" + this.banner_slides_class , this.element).height($("ul" + this.banner_slides_class + " li " + this.tag, this.element).height());
      $("ul" + this.banner_slides_class + " li", this.element).width(this.width);
      var width = this.width;

      // capture the original order of the elements and set their offsets
      var original_order = [];
      $("ul" + this.banner_slides_class + " li", this.element).each(function(i) {
        var left = (i*width);
        $(this).css({position:'absolute',left: left + 'px',top:0});
        original_order.push({left:left, element:this});
      });
      this.original_order = original_order;

      // fix rotate width to 1 element
      $(this.element).width(this.width);
      //$(this.element).height($("ul" + this.banner_slides_class + " li " + this.tag, this.element).height());
      $(this.element).css({overflow:'hidden'});

      var self = this;
      $("ul.dots li a", this.element).click(function(e) { e.preventDefault();
        self.clickJump(this);
      });

      // test case
      /*this.slide(2,function() {
        this.slide(0,function() {
          this.slide(1,function() {
            this.slide(2);
          }.bind(this));
        }.bind(this));
      }.bind(this));*/

      // replace img tags with canvas tags for easy animation and HW accelerated where available
      /*$("ul.slides a img").each(function() {
        canvas = document.createElement("canvas");
        canvas.setAttribute("width", $(this).width());
        canvas.setAttribute("height", $(this).height());
        var ctx = canvas.getContext("2d");
        if (ctx) {
          ctx.drawImage($(this)[0], 0, 0);
          $(this).parent().html(canvas);
          this.tag = 'canvas';
        }
      });*/

      this.autoFlip();
      //this.slide();
    },
    // pop current node to the end
    stage: function() {
      // move the current next_index to be greater than current_index in position
      $("ul" + this.banner_slides_class + " li:nth-child(" + (this.current_index+1) + ")").css({left:this.width +'px'});
    },
    // restore the original order using the original_order array
    restore: function() {
      // make sure to reveal current_index
    },

    /*

      ({[1]} [2] [3])

      ([1] {[2]} [3])

      ([1] [2] {[3]})

        ([2] {[3]} [1])

      ([2] [3] {[1]})

      or, first is always the visible and we're always animating 0 to -width and maybe 0 opacity
          after the animation we send current to the far right and shift each slide to the left by width

      {[1]} [2] [3]
      {[2]} [3] [1]
      {[3]} [1] [2]
      {[1]} [2] [3]

      // shift outer box to the left
      // once animate is done, update offsets
      // 1 takes 3's position, 2 takes 1's position, and 3 takes 2's position

    */

    nextHandler: function(cb) {
      if (this.nextClick != null && this.nextClick != undefined) {
        var next = this.nextClick;
        this.nextClick = null;
        this.slide(next);
      }
      else if (cb) {
        cb();
      }
    },

    reposition: function(cb) {
      if (!this.animating_current && !this.animating_next) {
        var width = this.width;
        var total = this.length;

        // reposition elements assuming they're already in sequenial order
        this.reshiftRight( (this.current_index+1) >= this.length ? 0 : (this.current_index+1) );
        
        this.nextHandler(cb);

      }
    },
    reshiftLeft: function(next_index) {
      var current = $("ul" + this.banner_slides_class + " li:nth-child(" + (this.current_index+1) + ")", this.element);
      var next    = $("ul" + this.banner_slides_class + " li:nth-child(" + (next_index+1) + ")", this.element);

      // when we're jumping we need to make sure the item we're jumping to is set up to be the next element e.g.
      // position it to left:-1 * width, so it appears to come from the left
      var width = this.width;
      var total = this.length;
      var current_index = this.current_index;

      // reset all to zero
      $("ul" + this.banner_slides_class + " li").css({left:0,opacity:0,visibility:'hidden'});

      // set the next to the correct offset
      $("ul" + this.banner_slides_class + " li:nth-child(" + (next_index+1) + ")", this.element).css({left:(-1*width)});

      $("ul" + this.banner_slides_class + " li:nth-child(" + (current_index+1) + ")", this.element).css({left:0,opacity:1,visibility:'visible'});

      // recalculate all other offsets from width + (index*width)
      $("ul" + this.banner_slides_class + " li").each(function(index,item) {
        if (index != current_index && index != next_index) {
          //console.log("update: " + index + ", " + next_index + ", " + current_index);
          $(this).css({left: (width+(index*width)) }); // still offset by width?
        }
      });
    },
    reshiftRight: function(next_index) {
      var current = $("ul" + this.banner_slides_class + " li:nth-child(" + (this.current_index+1) + ")", this.element);
      var next    = $("ul" + this.banner_slides_class + " li:nth-child(" + (next_index+1) + ")", this.element);
      
      if ( parseInt(next.css("left")) != this.width) {
        // when we're jumping we need to make sure the item we're jumping to is set up to be the next element e.g.
        // position it to left:width
        var width = this.width;
        var total = this.length;
        var current_index = this.current_index;

        // reset all to zero
        $("ul" + this.banner_slides_class + " li").css({left:0,opacity:0,visibility:'hidden'});
        // set the next to the correct offset
        $("ul" + this.banner_slides_class + " li:nth-child(" + (next_index+1) + ")", this.element).css({left:width});
        $("ul" + this.banner_slides_class + " li:nth-child(" + (current_index+1) + ")", this.element).css({left:0,opacity:1,visibility:'visible'});
        // recalculate all other offsets from width + (index*width)
        $("ul" + this.banner_slides_class + " li").each(function(index,item) {
          if (index != current_index && index != next_index) {
            //console.log("update: " + index + ", " + next_index + ", " + current_index);
            $(this).css({left: (width+(index*width)) });
          }
        });
      }
    },
    //
    // transition from the current index to the next index or jump to index if it's defined
    //
    slide: function(index, cb) {
      if (index != undefined) { // jump to a non sequential index
        var next_index = index;
      }
      else {
        var next_index = this.current_index + 1;
      }

      var cycleFromEnd = false;
      if (next_index >= this.length) { next_index = 0; cycleFromEnd = true } // we hit the end
      if (next_index == this.current_index) { return; } // no change
      
      // update the dots
      $("ul.dots li.selected").removeClass("selected");
      $("ul.dots li:nth-child(" + (next_index+1) + ")").addClass("selected");

      if (next_index > this.current_index || cycleFromEnd) {
        this.reshiftRight(next_index);
        var direction = -1;
      }
      else {
        // really only if the user clicked
        this.reshiftLeft(next_index);
        var direction = 1;
      }

      // start 2 animations immediately
      var current = $("ul" + this.banner_slides_class + " li:nth-child(" + (this.current_index+1) + ")", this.element);
      var next    = $("ul" + this.banner_slides_class + " li:nth-child(" + (next_index+1) + ")", this.element);

      if (this.options.transition == 'slider') {
        this.animating_current = true;
        current.animate({left:direction*this.width,opacity:0}, { duration:this.options.rotation_duration, complete: function() {
          this.current_index = next_index;
          this.animating_current = false;
          this.reposition(cb);
        }.bind(this)});

        this.animating_next = true;
        next.css({visibility:'visible'});
        next.animate({left:0,opacity:1}, { duration:this.options.rotation_duration, complete: function() {
          this.current_index = next_index;
          this.animating_next = false;
          this.reposition(cb);
        }.bind(this)});
      }
      else {
        // cross fade
        next.css({visibility:'visible'});
        next.hide();
        next.css({left:0,opacity:1});

        var afterOut = function() {
          this.current_index = next_index;
          this.animating_current = false;
          current.css({left:direction*this.width});
          this.reposition(cb);
        }.bind(this);

        var afterIn = function() {
          if (this.options.height) {
            this.shiftImage(next);
          }
          this.current_index = next_index;
          this.animating_next = false;
          this.reposition(cb);
        }.bind(this);

        this.animating_current = true;
        this.animating_next = true;

        if (this.jqv > 14.2) {
          current.fadeOut(1000,'linear', afterOut);
          next.fadeIn(1000,'linear', afterIn);
        } 
        else {
          current.fadeOut(1000, afterOut);
          next.fadeIn(1000, afterIn);
        }

      }

    },
    shiftImage: function(next, cb) {
      console.log("shiftImage");
      var img = next.find("img.dlgraphic");
      var delta = (parseInt(this.options.height) - parseInt(img.height()));
      //if (delta <= parseInt(img.css('top'))) {
      ////  delta = 0; // go back to zero 
      ////}
      var change = {top: (delta + 'px')};
      //img.animate(change, { queue:false, duration:60000, easing: 'linear', complete: cb });
    },
    autoFlip: function() {
      if (this._stop) { return; }
      this.pause();
      this.autoTimer = setTimeout(function() { this.slide(undefined, this.autoFlip.bind(this)); }.bind(this), this.options.rotation_delay);
    },
    pause: function() {
      if (this.autoTimer) { clearTimeout(this.autoTimer); this.autoTimer = null; }
    },
    stop: function() {
      this.pause();
      this._stop = true;
    },
    clickJump: function(dot) {
      this.stop();
      var clicked = $("ul.dots li a").index($(dot)); //parseInt($(dot).attr("href").replace(/#/,'')) - 1;
      if (!this.animating_current && !this.animating_next) {
        this.slide(clicked);
      }
      else {
        // setup a next action so the user doesn't need to click again
        this.nextClick = clicked;
      }
    }
  };

  $.fn.extend({
    dl: function(options) {
      var defaults = {
        rotation_delay: 5000,
        rotation_duration: 2250,
        banner_slides_class: '.slides',
        transition: 'slider',
        height: undefined
      };

      var options =  $.extend(defaults, options);

      return this.each(function() {
        new Banner(this, options);
      });
    }
  });
})(jQuery);
