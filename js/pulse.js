
$(document).ready(function(e) {

  $('body').pulse({
    style: 'test',
  });

});

// Type safe declaration
(function( window, $, undefined ) {

  'use strict';

  $.Pulse = function( options, element ) {
    this.element = $( element );
    this._create( options );
    this._init();
  };

  /**** Global variables ****/
  var projects = [];
  var projectImages = [];
  //var $container = $('#main_projects'); // breaks masonry
  // etc etc
  /**** End global variables ****/

  /**** Helper functions ****/
  // Helper string functions
  if (typeof String.prototype.startsWith != 'function') {
    String.prototype.startsWith = function (str){
      return this.slice(0, str.length) == str;
    };
  }
  if (typeof String.prototype.endsWith != 'function') {
    String.prototype.endsWith = function (str){
      return this.slice(-str.length) == str;
    };
  }
  if (typeof String.prototype.capitalize != 'function') {
    String.prototype.capitalize = function() {
      return this.charAt(0).toUpperCase() + this.slice(1);
    };
  }
  // End helper string functions
  // Helper cookie functions
  var createCookie = function(name,value,days) {
    var expires = '';
    if (days) {
      var date = new Date();
      date.setTime(date.getTime()+(days*24*60*60*1000));
      expires = "; expires="+date.toGMTString();
    } else {
      expires = "";
    }
    // If we're loading from a local file, set cookie using $.data
    if (window.location.hostname == '') {
      var cs = $('body').data('cookie');
      var c = name + "=" + value + expires + "; path=/;";
      $('body').data('cookie', cs ? cs + c : c);
    } else {
      document.cookie = name+"="+value+expires+"; path=/";
    }
  };
  var readCookie = function(name) {
    var nameEQ = name + "=";
    var ca = '';
    // If we're loading from a local file, get cookie using $.data
    if (window.location.hostname == '') {
      var cs = $('body').data('cookie');
      if (typeof(cs) != 'undefined') {
        ca = cs.split(';');
      }
    } else {
      ca = document.cookie.split(';');
    }
    for(var i=0;i < ca.length;i++) {
      var c = ca[i];
      while (c.charAt(0)==' ') c = c.substring(1,c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
  };
  var eraseCookie = function(name) {
    createCookie(name,"",-1);
  }
  // End helper cookie functions
  // URL parsing helpers
  var getUrlVars = function() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
      vars[key] = value;
    });
    return vars;
  };
  // End url parsing helpers
  /**** End helper functions ****/

  $.Pulse.settings = {
    style: 'test',
    title: 'Wikipedia Pulse',
  };

  /**** Prototype object ****/
  $.Pulse.prototype = {
    // Init is triggered when instance is created
    _init : function ( callback ) {
      this.iFunc = 'PulseInit';
    },
    // Sets up the widget
    _create : function( options ) {
      this.options = $.extend( true, {}, $.Pulse.settings, options );
      var instance = this;

      // Setup masonry and infinitescroll plugins
      var $container = $('#main_projects');
      $container.imagesLoaded( function() {
        $container.masonry({
          itemSelector : '.pulse_activeProject',
          isAnimated: true,
          gutterWidth: 1,
          isFitWidth: false, // don't center
        });
      });
      $container.infinitescroll({
        navSelector:  '#page-nav',            // selector for the paged navigation
        nextSelector: '#page-nav a',          // selector for the NEXT link (to page 2)
        itemSelector: '.pulse_activeProject', // selector for all items you'll retrieve
        debug: false,
        loading: {
            finishedMsg: 'No more pages to load.',
            img: 'http://i.imgur.com/6RMhx.gif',
            msgText: 'Loading the next group of WikiProjects...',
          }
        },
        // trigger Masonry as a callback
        function( newElements ) {
          // hide new items while they are loading
          var $newElems = $( newElements ).css({ opacity: 0 });
          // ensure that images load before adding to masonry layout
          $newElems.imagesLoaded(function(){
            // show elems now they're ready
            $newElems.animate({ opacity: 1 });
            $container.masonry( 'appended', $newElems, true );
            instance._addProjectActions();
            instance._addProjectDescriptions();
          });
        }
      );

      // Add detail information to each project
      this._addProjectActions();
      this._addProjectDescriptions();
    },
    _addProjectDescriptions : function() {
      $('.pulse_activeProject').each(function() {
        // ID will be the page id, parent div is <id>_image_div, image id is <id>_image
        $(this).hover(
          function() {
            // If we already have the description, show it
            if ($('#' + this.id + '_desc').length != 0) {
              $('#' + this.id + '_desc').fadeIn('slow');
              return true;
            }
            // Otherwise, create the description div
            var p_id = this.id;
            var pi_id = $('#' + this.id + '_image').attr('pi_id');
            var pi_img = $('#' + this.id + '_image').attr('pi_img');
            var desc = document.createElement("div");
            desc.id = this.id + '_desc';
            $(desc).addClass("pulse_descProject");
            $(desc).html(
              "<div id='" + this.id + "_desc_title' class='desc_title'></div>" +
              "<div id='" + this.id + "_desc_text' class='desc_text'></div>"
            );
            // And create the ellipsis with mouseover for more actions
            var full = document.createElement("div");
            full.id = this.id + '_full';
            $(full).addClass("pulse_fullDescProject");
            $(full).html("...");

            // Append all desc elements
            $(desc).append(full);
            $('#' + this.id + '_image_div').append(desc);
            $('#' + this.id + '_desc').hide();
            $('#' + this.id + '_desc').fadeIn('fast');

            // Add description actions
            $(full).click(function() {
              // Increase/decrease text description div and include full description
              if ($('#' + p_id + '_desc_text').data('showing') == 'partial') {
                $('#' + p_id + '_desc').css({
                  'height': '175px', 'margin-bottom': '-110px', 'z-index': '10', 'background-color': 'rgba(255,255,255,1)'
                });
                $('#' + p_id + '_desc_text').html($('#' + p_id + '_desc_text').data('full'));
                $('#' + p_id + '_desc_text').data('showing', 'full');
                // Show project/article/pulse page links
                var links = document.createElement("div");
                links.id = p_id + '_desc_links';
                $(links).addClass('pulse_descLinks');
                var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
                url = url.replace('index.php', 'projects.php');
                var prLink = $('#' + p_id + '_desc_text').data('project');
                var arLink = $('#' + p_id + '_desc_text').data('article');
                var puLink = url + "/projects.php?p_id=" + p_id;
                $(links).html(
                  "<div class='pulse_descLink' title='View project page'><a href='" + prLink + "'>Pr</a></div>" +
                  "<div class='pulse_descLink' title='View article page'><a href='" + arLink + "'>Ar</a></div>" +
                  "<div class='pulse_descLink' title='View pulse page'><a href='" + puLink + "'>Pu</a></div>"
                );
                $('#' + p_id + '_desc').append(links);
              } else {
                $('#' + p_id + '_desc').css({
                  'height': '65px', 'margin-bottom': '0px', 'background-color': 'rgba(255,255,255,.8)'
                });
                $('#' + p_id + '_desc_text').html($('#' + p_id + '_desc_text').data('partial'));
                $('#' + p_id + '_desc_text').data('showing', 'partial');
                $('#' + p_id + '_desc_links').remove();
              }
            });

            // Insert the description text
            var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
            //  var request = url + "?a=utility_upVoteImage&pi_id=" + pi_id + "&p_id=" + this.id + "&piv_vote=1";
            var request = url + "?a=utility_getPageDescription&p_id=" + p_id + "&pi_id=" + pi_id;
            $.getJSON(request, function(data) {
              // Check for errors
              if (data.errorstatus == 'fail') {
                $('#' + p_id + '_desc_title').html(data.title);
                $('#' + p_id + '_desc_text').html(data.message);
              } else {
                $('#' + p_id + '_desc_title').html(data.title);
                var over = data.description.length > 150 ? "..." : "";
                var desc = data.description.substring(0, 100);
                var fullOver = data.description.length > 350 ? "..." : "";
                var fullDesc = data.description.substring(0, 350);
                $('#' + p_id + '_desc_text').data('full', fullDesc + fullOver);
                $('#' + p_id + '_desc_text').data('partial', desc + over);
                $('#' + p_id + '_desc_text').data('showing', 'partial');
                $('#' + p_id + '_desc_text').data('project', data.project);
                $('#' + p_id + '_desc_text').data('article', data.article);
                $('#' + p_id + '_desc_text').html(desc + over);
              }
            });
          },
          function() {
            $(this).css({ opacity: 1 });
            // Hide the vote div
            $('#' + this.id + '_desc').fadeOut('slow');
          }
        );
      });
    },
    // Adds the vote buttons, refresh button, and project detail information to each image
    _addProjectActions : function() {
      var $container = $('#main_projects');
      $('.pulse_activeProject').each(function() {
        // ID will be the page id, parent div is <id>_image_div, image id is <id>_image
        //console.log("ID: " + this.id);
        $(this).hover(
          function() {
            //$(this).css({ opacity: .7 });

            // Create the voting div, if necessary
            if ($('#' + this.id + '_vote').length != 0) { 
              $('#' + this.id + '_vote').fadeIn('slow');
              return true; 
            }
            var p_id = this.id;
            var pi_id = $('#' + this.id + '_image').attr('pi_id');
            var pi_img = $('#' + this.id + '_image').attr('pi_img');
            var piv_vote = $('#' + this.id + '_image').attr('piv_vote');
            var vote = document.createElement("div");
            vote.id = this.id + '_vote';
            $(vote).addClass("pulse_voteProject");

            // Up vote button
            var upVote = document.createElement("img");
            upVote.id = this.id + '_upvote';
            $(upVote).attr({ src: "img/thumbs_up.svg", width: "25px", height: "25px" });
            if (piv_vote == 1) { $(upVote).addClass('pulse_voted'); }

            // Down vote button
            var downVote = document.createElement("img");
            downVote.id = this.id + '_downvote';
            $(downVote).attr({ src: "img/thumbs_down.svg", width: "25px", height: "25px" });
            if (piv_vote == 0 && piv_vote != '') { $(downVote).addClass('pulse_voted'); }

            // Refresh pic button
            var updatePic = document.createElement("img");
            updatePic.id = this.id + '_updatepic';
            $(updatePic).attr({ src: "img/refresh.svg", width: "25px", height: "25px" });
            // Track which img's have been viewed for this project
            projectImages[p_id] = [pi_img];

            // Append all vote actions
            $(vote).append(upVote);
            $(vote).append(updatePic);
            $(vote).append(downVote);
            $('#' + this.id + '_image_div').append(vote);
            $('#' + this.id + '_vote').hide();
            $('#' + this.id + '_vote').fadeIn('slow');

            // Add voting div actions
            $(upVote).click(function() {
              console.log("Up voting " + this.id + ", img_id: " + pi_id);
              var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
              var request = url + "?a=utility_upVoteImage&pi_id=" + pi_id + "&p_id=" + this.id + "&piv_vote=1";
              $.getJSON(request, function(data) {
                if (data.errorstatus == 'fail') {
                  console.log("Failed: " + data.message);
                } else {
                  console.log("Succeeded: " + data.message);
                  $('#' + p_id + '_upvote').addClass('pulse_voted');
                  $('#' + p_id + '_image').attr('piv_vote', 1);
                }
              });
            });
            $(downVote).click(function() {
              console.log("Down voting " + p_id + ", img_id: " + pi_id);
              var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
              var loading = document.createElement("div");
              loading.id = p_id + '_loading';
              $(loading).addClass('pulse_loadingImage');
              $('#' + p_id + '_image_div').append(loading);
              $.post(url, {
                  a: 'utility_downVoteImage',
                  pi_id: pi_id,
                  p_id: p_id,
                  piv_vote: '-1',
                  projectImages: projectImages[p_id],
                }, function(data) {
                  console.log("Returned: " + data.message);
                  $('#' + p_id + '_loading').remove();
                  if (data.errorstatus == "fail") {

                  } else {
                    projectImages[p_id].push(data.pi_img);
                    $(updatePic).trigger('click');
                  }
                }, "json"
              );
            });
            $(updatePic).click(function() {
              console.log("Refreshing " + p_id + ", img_id: " + pi_id);
              var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
              // Add the loading icon
              var loading = document.createElement("div");
              loading.id = p_id + '_loading';
              $(loading).addClass('pulse_loadingImage');
              $('#' + p_id + '_image_div').append(loading);
              $.post(url, { 
                  a: 'utility_refreshImage',
                  pi_id: pi_id,
                  p_id: p_id,
                  projectImages: projectImages[p_id],
                }, function(data) {
                  // Update the image
                  $('#' + p_id + '_loading').remove();
                  $('#' + p_id + '_image').attr({ 
                    src: data.href, width: data.width, height: data.height, pi_id: data.pi_id, 
                    pi_img: data.pi_img, piv_vote: data.piv_vote 
                  });
                  pi_id = data.pi_id; piv_vote = data.piv_vote; pi_img = data.pi_img;

                  if (data.piv_vote && data.piv_vote == 1) {
                    $('#' + p_id + '_upvote').addClass('pulse_voted');
                    $('#' + p_id + '_downvote').removeClass('pulse_voted');
                  } else if (data.piv_vote && data.piv_vote == -1) {
                    $('#' + p_id + '_downvote').addClass('pulse_voted');
                    $('#' + p_id + '_upvote').removeClass('pulse_voted');
                  } else {
                    $('#' + p_id + '_downvote').removeClass('pulse_voted');
                    $('#' + p_id + '_upvote').removeClass('pulse_voted');
                  }
                  $('#' + p_id + '_desc').remove();
                  $container.masonry( 'reload' );

                  // Push the image on the project images array
                  projectImages[p_id].push(data.pi_img);
                }, "json"
              );

            });
          },
          function() {
            $(this).css({ opacity: 1 });
            // Hide the vote div
            $('#' + this.id + '_vote').fadeOut('slow');
          }
        );
      });
    },
    // Updates to this should also be made to the PHP utility_scaleImage function
    _scaleImage : function(width, height) {
      var mult = 0; var col = 1;
      mult = 304/width;
      // We'll want to scale image so width is 304px, or a multiple of that up to * 3
/*
      if (width < 456) {
        // Target width: 304
        mult = 304 / width;
      //} else if (width >= 456 && width < 760) {
      } else { 
        // Target width: 608
        mult = 608 / width;
        col = 2;
      } /* else {
        // Target width: 912
        mult = 912 / width;
      }*/
      return { 'width': parseInt(width * mult), 'height': parseInt(height * mult), 'col': col };
    },
  };
  /**** End pulse prototype object ****/

  $.fn.pulse = function( options ) {
    if ( typeof options === 'string' ) {
      var args = Array.prototype.slice.call( arguments, 1 );
      this.each(function() {
        var instance = $.data( this, 'pulse' );
        if (!instance ) {
          logError( "cannot call methods on reflex prior to initialization.  Attempted to call method '" + options);
          return;
        }
        // apply method
        instance[ options ].apply( instance, args );
      });
    } else {
      this.each(function() {
        var instance = $.data( this, 'pulse' );
        if ( instance ) {
          instance.option( options || {} );
          instance._init();
        } else {
          $.data( this, 'pulse', new $.Pulse( options, this ) );
        }
      });
    }
  };

})( window, jQuery);

