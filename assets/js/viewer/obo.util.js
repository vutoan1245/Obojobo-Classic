/**
	util simply contains helper functions
*/

if(!window.obo)
{
	window.obo = {};
}

obo.util = function()
{
	// @private
	var pReplaceRules = [
		['align', 'text-align', ''],
		['face', 'font-family', ''],
		['color', 'color', ''],
		['size', 'font-size', 'px'],
		['letterspacing', 'letter-spacing', 'px']
	];

	var tfReplaceRules = [
		['indent', 'text-indent', 'px'],
		['leftmargin', 'margin-left', 'px'],
		['rightmargin', 'margin-right', 'px']
	];
	
	var patternStrictTfToLI = /<\s*textformat([a-z=A-Z"'0-9 ]*)?\s*><\s*li\s*>/gi;
	var patternStrictTfToDiv = /<\s*textformat(\s+.*?=(?:"|').*?(?:"|'))\s*>/gi;
	var patternStrictTFClose = /<\/li><\/textformat>/gi;
	var patternStrictPFont = /<\s*p(\s+align=(?:"|')(?:left|right|center|justify)(?:"|'))?\s*><\s*font(\s+.*?=(?:"|').*?(?:"|'))\s*>/gi;
	var patternStrictPFontClose = /<\/font><\/p>/gi;
	var patternStrictFont = /<\s*font(\s+.*?=(?:"|').*?(?:"|'))\s*>/gi;
	var patternStrictFontClose = /<\/font>/gi;
	var patternStrictRemoveUL = /<\/?ul>/gi;
	var patternStrictAddUL = /(<li(.*?)?>([\s\S]*?)<\/li>)/gi;
	var patternStrictRemoveExtraUL = /<\/ul><ul>/gi;
	var patternStrictEmpty1 = /<(\w+?)[^>]*?>(\s*?)<\/\1>/gi;
	var patternStrictEmpty2 = /<(\w+)>(\s*?)<\/\1>/gi;
	var patternStrictOMLTooltip = /\[\s*?tooltip\s+?text\s*?=\s*?(?:"|')(.+?)(?:"|')\s*?](.+?)\[\/tooltip\]/gi;
	var patternStrictOMLPageLink = /\[\s*?page\s+?id\s*?=\s*?(?:"|')(.+?)(?:"|')\s*?](.+?)\[\/page\]/gi;
	
	var patternTF = /<\/?textformat\s?.*?>/gi;
	var patternPFont = /<p\s?(.*?)><font.*?(?:FACE="(\w+)").*?(?:SIZE="(\d+)").*?(?:COLOR="(#\d+)").*?>/gi;
	var patternPFontClose = /<\/font><\/p>/gi;
	var patternFont = /<font.*?(?:KERNING="\d+")?.*?(?:FACE="(\w+)")?.*?(?:SIZE="(\d+)")?.*?(?:COLOR="(#\d+)")?.*?>/gi;
	var patternFontClose = /<\/font>/gi;
	var patternEmpty1 = /<(\w+?)[^>]*?>(\s*?)<\/\1>/gi;
	var patternEmpty2 = /<(\w+)>(\s*?)<\/\1>/gi;
	var patternRemoveUL = /<\/?ul>/gi;
	var patternAddUL = /<LI>([\s\S]*?)<\/LI>/gi;
	var patternRemoveExtraUL = /<\/ul><ul>/gi;
	
	// @TODO ul and p should have margin = 0
	/** This attempts to recreate HTML from flash HTML exactly **/
	var cleanFlashHTMLStrict = function(input)
	{
		var groups;
		var groupString;
		var lastIndex;
		
		//Convert <textformat ...><li> into <li style='...'>
		var matchFound = true;
		while(matchFound)
		{
			groups = patternStrictTfToLI.exec(input);
			lastIndex = patternStrictTfToLI.lastIndex;
			if(groups && groups.length >= 2)
			{
				groupString = groups[1];
				var style = generateStyleFromFlashHTMLTag(groupString, tfReplaceRules);
				//We only want to add this span if there are styles associated with it
				if(style.length > 0)
				{
					input = input.substr(0, lastIndex).replace(patternStrictTfToLI, '<li style="' + style + '">') + input.substr(lastIndex);
				}
				else
				{
					input = input.substr(0, lastIndex).replace(patternStrictTfToLI, '<li>') + input.substr(lastIndex);
				}
			}
			else
			{
				matchFound = false;
			}
		}
		
		input = input.replace(patternStrictTFClose, "</li>");
//patternStrictTfToDiv.lastIndex = 0;
		var matchFound = true;
		// @TODO: handle textformat with no style options
		//Convert <textformat> into <div>
		var i = 0;
		//console.log('convert textformat into div');
		//console.log('input start = ' + input);
		while(matchFound)
		{
			matchFound = false;
			//console.log('i >    =====' + i);
			i = i + 1;
			groups = patternStrictTfToDiv.exec(input);
			lastIndex = patternStrictTfToDiv.lastIndex;
			if(groups && groups.length >= 2)
			{
				groupString = groups[1];
				var style = generateStyleFromFlashHTMLTag(groupString, tfReplaceRules);
				//We only want to add this span if there are styles associated with it
				//console.log('lastIndex = ' + patternStrictTfToDiv.lastIndex);
				//console.log('input = "' + input.substr(0, patternStrictTfToDiv.lastIndex) + '".replace(pattern, stuff) + "' + input.substr(patternStrictTfToDiv.lastIndex) + '"');
				if(style.length > 0)
				{
					input = input.substr(0, lastIndex).replace(patternStrictTfToDiv, '<div style="' + style + '">') + input.substr(lastIndex);
				}
				else
				{
					input = input.substr(0, lastIndex).replace(patternStrictTfToDiv, '<div>') + input.substr(lastIndex);
				}
			}
			else
			{
				matchFound = false;
			}
			//console.log('input now   = ' + input);
		}
//return 'test';
		input = input.replace(patternStrictTFClose, "</div>");
		
		//Convert <p><font> into <p>
		var matchFound = true;
		var j = 0;
		//console.log('input start = ' + input)
		while(matchFound)
		{
				//console.log('j >    =====' + j);
				j = j + 1;
			
			groups = patternStrictPFont.exec(input);
			lastIndex = patternStrictPFont.lastIndex;
			if(groups && groups.length >= 3)
			{
				//console.log('input now   = ' + input);
				groupString = groups[1] + ' ' + groups[2];
				//console.log(patternStrictPFont);
				//console.log('lastindex =' + patternStrictPFont.lastIndex);
				
				input = input.substr(0, lastIndex).replace(patternStrictPFont, '<p style="' + generateStyleFromFlashHTMLTag(groupString, pReplaceRules) + '">') + input.substr(lastIndex);
				
			}
			else
			{
				matchFound = false;
			}
			//console.log('input now =');
			//console.log(input);
			//console.log(' ');
			//matchFound = false; // @TODO
			//if(j > 3) return;
		}
		
		input = input.replace(patternStrictPFontClose, "</p>");

		//Convert single <font> into <span>
		var matchFound = true;
		while(matchFound)
		{
			groups = patternStrictFont.exec(input);
			lastIndex = patternStrictFont.lastIndex;
			if(groups && groups.length >= 2)
			{
				groupString = groups[1];
				input = input.substr(0, lastIndex).replace(patternStrictFont, '<span style="' + generateStyleFromFlashHTMLTag(groupString, pReplaceRules) + '">') + input.substr(lastIndex);
			}
			else
			{
				matchFound = false;
			}
			matchFound = false; // @TODO
		}
		
		input = input.replace(patternStrictFontClose, "</span>");
		
		// remove any previously added ul tags
		input = input.replace(patternStrictRemoveUL, "");

		// add <ul></ul> arround list items
		input = input.replace(patternStrictAddUL, "<ul>$1</ul>");

		// kill extra </ul><ul> that are back to back - this will make proper lists
		input = input.replace(patternStrictRemoveExtraUL, "");

		// find empty tags keeping space in them
		input = input.replace(patternStrictEmpty1, "$2");
		input = input.replace(patternStrictEmpty2, "$2");
		
		var matchFound = true;
		while(matchFound)
		{
			// find empty tags keeping space in them
			groups = patternStrictEmpty1.exec(input);
			lastIndex = patternStrictEmpty1.lastIndex;
			if(groups && groups.length >= 3)
			{
				groupString = groups[2];
				input = input.substr(0, lastIndex).replace(patternStrictEmpty1, groupString) + input.substr(lastIndex);
			}
			else
			{
				matchFound = false;
			}
			matchFound = false; // @TODO
		}

		var matchFound = true;
		while(matchFound)
		{
			groups = patternStrictEmpty2.exec(input);
			lastIndex = patternStrictEmpty2.lastIndex;
			if(groups && groups.length >= 3)
			{
				groupString = groups[1];
				input = input.substr(0, lastIndex).replace(patternStrictEmpty2, groupString) + input.substr(lastIndex);
			}
			else
			{
				matchFound = false;
			}
			matchFound = false; // @TODO
		}

		input = createOMLTags(input);

		return input;
	};
	
	// @TODO: This runs very very slow in FF
	var generateStyleFromFlashHTMLTag = function(attribs, rules)
	{
		var style = '';
		var reg;
		var match;
		for(var a in rules)
		{
			reg = new RegExp(rules[a][0] + '\s*=\s*(?:\'|")(.+?)(?:\'|")', 'gi');
			match = reg.exec(attribs)
			if(match != null && match.length >= 2)
			{
				style += rules[a][1] + ':' + match[1] + rules[a][2] + ';';
			}
		}
		return style;
	};

	var createOMLTags = function(input)
	{
		var pattern;

		/*Find OML tooltips*/
		// @TODO: Img
		input = input.replace(patternStrictOMLTooltip, '<span title="$1" class="oml oml-tip">$2</span>');

		/*Find OML page links*/
		// @TODO: Left or right arrow depending on if that would be foward or back
		input = input.replace(patternStrictOMLPageLink, '<a class="oml oml-page-link" data-page-id="$1" href="' + location.href + 'page/$1" title="' + '&rarr; Page $1">$2</a>');
		/*input = input.replace(pattern, '<a class="oml oml-page-link" data-page-id="$1" href="http://www.google.com/" title="Go to page $1">$2</a>');*/
		// @TODO: Fix quote issues (" --> &quot;)

		return input;
	};
	
	// @public
	getRGBA = function(colorInt, alpha)
	{
		if(Modernizr.rgba)
		{
			return 'rgba(' + ((colorInt >> 16) & 255) + ',' + ((colorInt >> 8) & 255) + ',' + (colorInt & 255) + ',' + alpha + ')';
		}
		else
		{
			return '#' + ('000000' + colorInt.toString(16)).slice(-6);
		}
	};
	
	// Old learning objects were saved using flash's textfields - which suck at html
	cleanFlashHTML = function(input, strict)
	{
		if(strict === true)
		{
			return cleanFlashHTMLStrict(input);
		}

		// get rid of all the textformat tags
		input = input.replace(patternTF, "");

		// combine <p><font>...</font></p> tags to just <p></p>
		// input = input.replace(pattern, '<p style="font-family:$2;font-size:$3px;color:$4;">');
		input = input.replace(patternPFont, '<p>');

		input = input.replace(patternPFontClose, "</p>");

		// convert lone <font>...</font> tags to spans
		// input = input.replace(pattern, '<span style="font-family:$1;font-size:$2px;color:$3;">');
		input = input.replace(patternFont, '<span>');

		input = input.replace(patternFontClose, "</span>");

		// find empty tags keeping space in them
		input = input.replace(patternEmpty1, "$2");
		input = input.replace(patternEmpty2, "$2");

		// remove any previously added ul tags
		input = input.replace(patternRemoveUL, "");

		// add <ul></ul> arround list items
		input = input.replace(patternAddUL, "<ul><li>$1</li></ul>"); // @TODO DOES THIS WORK??????????

		// kill extra </ul><ul> that are back to back - this will make proper lists
		input = input.replace(patternRemoveExtraUL, "");

		input = createOMLTags(input);

		return input;
	};
	
	strip = function(html)
	{
		return html.replace(/</g,'v').replace(/>/g,'&gt;').replace(/&/g,'&amp;').replace(/\"/g, '');
	};

	// @TODO: Get rid of this?
	/*
	getURLParam = function(strParamName)
	{
		var strReturn = "";
		var strHref = window.location.href;
		if ( strHref.indexOf("?") > -1 )
		{
			var strQueryString = strHref.substr(strHref.indexOf("?")).toLowerCase();
			var aQueryString = strQueryString.split("&");
			for ( var iParam = 0; iParam < aQueryString.length; iParam++ )
			{
				if ( aQueryString[iParam].indexOf(strParamName.toLowerCase() + "=") > -1 )
				{
					var aParam = aQueryString[iParam].split("=");
					strReturn = aParam[1];
					break;
				}
			}
		}
		return unescape(strReturn);
	};*/
	
	// given a answer array it will return the answer object matching answerID
	getAnswerByID = function(answers, answerID)
	{
		for(var i in answers)
		{
			if(answers[i].answerID === answerID)
			{
				return answers[i];
			}
		}

		return undefined;
	};
	
	isIOS = function()
	{
		return navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPod/i) || navigator.userAgent.match(/iPad/i);
	};
	
	// simple wrapper to delay a function call by 1ms
	doLater = function(func)
	{
		setTimeout(func, 1);
	};
	
	return {
		getRGBA: getRGBA,
		cleanFlashHTML: cleanFlashHTML,
		strip: strip,
		getAnswerByID: getAnswerByID,
		isIOS: isIOS,
		doLater: doLater
	};
}();