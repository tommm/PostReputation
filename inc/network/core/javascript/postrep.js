/*
+---------------------------------------------------------------------------
|   MyNetwork Core
|	|- Post Reputation Javascript
|   =============================================
|   by Tom Moore (www.xekko.co.uk)
|   Copyright 2011 Mooseypx Design / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: $
+---------------------------------------------------------------------------
*/

var PostReputation = {
	vote: function(pid, action, uid)
	{
		this.spinner = new ActivityIndicator("body", {image: imagepath + "/spinner_big.gif"});

		new Ajax.Request('xmlhttp.php',
		{
			method: 'post',
			parameters: { my_post_key: my_post_key, action: 'post_reputation', pid: pid, act: action },
			onSuccess: function(data)
			{
				PostReputation.update(data, pid, uid);
			},
			onFailure: function()
			{
				if(this.spinner)
				{
					this.spinner.destroy();
					this.spinner = '';
				}

				alert(failed_xmlhttprequest);
			}
		});
	},

	remove: function(pid, uid)
	{
		this.spinner = new ActivityIndicator("body", {image: imagepath + "/spinner_big.gif"});

		new Ajax.Request("xmlhttp.php",
		{
			method: "post",
			parameters: { my_post_key: my_post_key, action: 'remove_reputation', pid: pid },
			onSuccess: function(data)
			{
				PostReputation.update(data, pid, uid);
			},
			onFailure: function()
			{
				if(this.spinner)
				{
					this.spinner.destroy();
					this.spinner = '';
				}

				alert(failed_xmlhttprequest);
			}
		});
	},

	update: function(data, pid, uid)
	{
		if(this.spinner)
		{
			this.spinner.destroy();
			this.spinner = '';
		}

		if(data.responseText.match(/<error>(.*)<\/error>/))
		{
			message = data.responseText.match(/<error>(.*)<\/error>/);

			if(!message[1])
			{
				message[1] = "An unknown error occurred.";
			}

			alert('There was an error voting for this post.\n\n'+message[1]);
		}
		else
		{
			// Data is fine. Parse JSON.
			var JSONobject = data.responseText.evalJSON();
			$("post_rep_area_" + pid).update(JSONobject['rep_area']);
			
			if(JSONobject['user_rep'] != "false")
			{
				if(JSONobject['user_rep'] == null)
				{
					JSONobject['user_rep'] = 0;
				}

				var rep_class = "reputation_neutral";
				if(JSONobject['user_rep'] < 0)
				{
					rep_class = "reputation_negative";
				}
				else if(JSONobject['user_rep'] > 0)
				{
					rep_class = "reputation_positive";
				}

				$$('span[class="user_' + uid + '_rep"]').each(function(e)
				{
					if(e.innerHTML)
					{
						e.update('<a href="reputation.php?uid=' + uid + '"><strong class="' + rep_class + '">' + JSONobject['user_rep'] + '</strong></a>');
					}
				});
			}

			if(JSONobject['message'] != "")
			{
				alert(JSONobject['message']);
			}
		}
	}
}