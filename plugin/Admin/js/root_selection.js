"use strict";
jQuery(document).ready(function($) {
	function listGdriveDir(path)
	{
		$("#sgdg_root_selection_body").html("");
		$("#submit").attr("disabled", "disabled");
		$.get(sgdg_jquery_localize.ajax_url, {
			_ajax_nonce: sgdg_jquery_localize.nonce,
			action: "list_gdrive_dir",
			path: path
		}, function(data)
			{
				var html = "";
				if(path.length > 0)
				{
					html += "<tr><td class=\"row-title\"><label>..</label></td></tr>";
				}
				for(var i = 0; i < data.contents.length; i++)
				{
					html += "<tr class=\"";
					if((path.length === 0 && i % 2 === 1) || (path.length > 0 && i % 2 === 0))
					{
						html += "alternate";
					}
					html += "\"><td class=\"row-title\"><label data-id=\"" + data.contents[i].id + "\">" + data.contents[i].name + "</label></td></tr>";
				}
				$("#sgdg_root_selection_body").html(html);
				html = "";
				if(path.length === 0)
				{
					html = sgdg_jquery_localize.team_drive_list
				}
				else
				{
					$("#submit").removeAttr("disabled");
				}
				for(i = 0; i < path.length; i++)
				{
					if(i > 0)
					{
						html += " > ";
					}
					html += data.path[i];
				}
				$(".sgdg_root_selection_path").html(html);
				$("#sgdg_root_selection_body label").click(function()
					{
						var newId = $(this).attr("data-id")
						if(newId)
						{
							path.push(newId);
						}
						else
						{
							path.pop();
						}
						listGdriveDir(path);
					});
				$("#sgdg_root_path").val(JSON.stringify(path));
			});
	}

	listGdriveDir(sgdg_jquery_localize.root_dir);
});
