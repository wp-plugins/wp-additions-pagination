(function($) 
{
 
  $(document).ready(function()
  {
    wpa_pagination.init(wpa_pagination_options);
  });
  
  var wpa_pagination = {
    
    options: {
			prefix: 'wpa-'
			,ajax_url: ''
			,type: ''
			,show_rows: ''
			,ajax_action: 'wpa_pagination'
			,types: {}
			,filters: {}      
    }
    
    ,init: function(options)
    {
      $.extend(
        wpa_pagination.options
        ,options
      );
      
      $('div.tablenav-pages').each(function() 
      {
        var pagination = {
          data: $(this).contents('span.displaying-num').text().split(' ')
          ,start: 1
          ,max: 1
        };
        if (pagination.data[0].indexOf(wpa_pagination.options.display.prefix) >= 0)
        {
          var t = pagination.data[1].split('–');
          wpa_pagination.options.show_rows = wpa_pagination.options.show_rows ? wpa_pagination.options.show_rows : parseInt(t[1].replace(',', ''));
          pagination.start = wpa_pagination.options.show_rows / wpa_pagination.options.show_rows;
          pagination.max = Math.ceil(parseInt(pagination.data[pagination.data.length - 1].replace(',', '')) / wpa_pagination.options.show_rows);
          wpa_pagination.options.count = pagination.data[pagination.data.length - 1];
        }
    
        if (pagination.start && pagination.max)
        {
          $(this).html('<span class="' + wpa_pagination.options.prefix + 'slider"></span><span class="' + wpa_pagination.options.prefix + 'slider-pages displaying-num">' + wpa_pagination.update_display(pagination.start) + '</span>')
            .contents('span.' + wpa_pagination.options.prefix + 'slider')
            .slider({
        			value: pagination.start
        			,orientation: 'horizontal'
        			,min: 1
        			,max: pagination.max
        			,step: 1
        			,slide: function(event, ui) 
        			{
        			  $('div.tablenav-pages span.' + wpa_pagination.options.prefix + 'slider-pages')
        			    .text(wpa_pagination.update_display(ui.value));
    
        			  $('div.tablenav-pages span.' + wpa_pagination.options.prefix + 'slider').each(function() 
                {
                  $(this)
                    .slider('value', ui.value);
        			  });
        			}
              ,start: function(event, ui) 
              {
                $('table.widefat.fixed tbody:first')
                  .css('opacity', 0.35);
              }
        			,stop: function(event, ui) 
        			{
        			  var data = {};
                var filters_changed = false;
               	for (var i in wpa_pagination.options.types)
              	{
                  if (i == wpa_pagination.options.type)
                  {
                    for (var x in wpa_pagination.options.types[i])
                    {
                      value = $(wpa_pagination.options.types[i][x] + '[name=' + x + ']').val();
                      if (typeof(value) != 'undefined' && value != '' && value != 0)
                      {
                        data[x] = value;
                        if (!wpa_pagination.options.filters[x])
                        {
                          filters_changed = true;
                        }
                      }
                    }
                  }
                }

                $.extend(
                  data
                  ,wpa_pagination.options.filters
                  ,{
                		action: wpa_pagination.options.ajax_action
                		,pagination_offset: ui.value
                		,pagination_type: wpa_pagination.options.type
                		,pagination_show_rows: wpa_pagination.options.show_rows
                	}
                );

                if (filters_changed)
                {
                  $('input[name=_wpnonce]')
                    .closest('form')
                    .submit();
                }
                else
                {
                  $.post(wpa_pagination.options.ajax_url
                    ,data
              	    ,function(xml) 
                    {
                      var r = wpAjax.parseAjaxResponse(xml);
                      if (r.responses[0].data != '0' && r.responses[0].what == wpa_pagination.options.type)
                      {
                        $('table.widefat.fixed tbody:first')
                          .html(r.responses[0].data);
                      }
                      $('table.widefat.fixed tbody:first')
                        .css('opacity', 1);
                      
                      if (wpa_pagination.options.types[wpa_pagination.options.type]._callback)
                      {
                        wpa_pagination.execute(wpa_pagination.options.types[wpa_pagination.options.type]._callback, window, null);
                      }
                      
                      
                    }
                  );              
                }
        			}    
        		});
    
          $(this)
            .contents()
            .show();
        }
    
      });
    }

    ,update_display: function(value)
    {
      var start = (value * wpa_pagination.options.show_rows) - wpa_pagination.options.show_rows;
      start = (start > 0 ? start : 1);
  
      var end = (value * wpa_pagination.options.show_rows);
      end = (end > wpa_pagination.options.count ? wpa_pagination.options.count : end);
  
      return wpa_pagination.options.display.prefix + ' ' + wpa_pagination.add_commas(start) + '–' + wpa_pagination.add_commas(end) + ' ' + wpa_pagination.options.display.middle + ' ' + wpa_pagination.options.count;
    }
    
    ,execute: function(f, context, args) 
    {
      var namespaces = f.split(".");
      var func = namespaces.pop();
      for(var i = 0; i < namespaces.length; i++) 
      {
        context = context[namespaces[i]];
      }
      return context[func].apply(this, Array.prototype.slice.call(arguments).splice(2));
    }
    
    ,add_commas: function(string)
    {
    	string += '';
    	var x = string.split('.');
    	var a = x[0];
    	var b = x.length > 1 ? '.' + x[1] : '';
    	var regex = /(\d+)(\d{3})/;
    	while (regex.test(a)) 
    	{
    		a = a.replace(regex, '$1' + ',' + '$2');
    	}
    	return a + b;
    }
    

  }
  
})(jQuery);


//     
// Callbacks
// 

  function wpa_pagination_posts()
  {
    inlineEditPost.init();
    jQuery('.hide-if-no-js').removeClass('hide-if-no-js');
  }

  function wpa_pagination_comments()
  {
    columns.init('edit-comments');
    commentReply.init();
  }