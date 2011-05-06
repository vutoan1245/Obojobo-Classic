<?php
require_once(dirname(__FILE__)."/../../../internal/app.php");
// Check for super user
$API = \obo\API::getInstance();
$result = $API->getSessionRoleValid(array(\obo\perms\Role::SUPER_STATS));
if(! in_array(\obo\perms\Role::SUPER_STATS, $result['hasRoles']) )
{
	exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Prototype Stats</title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Ian Turgeon">
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/themes/base/jquery-ui.css" type="text/css" media="all" /> 
	<link rel="stylesheet" href="images/style.css" type="text/css" media="all" /> 
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/jquery-ui.min.js"></script>
	<script src="js/jquery.tablesorter.min.js"></script>
	<script src="js/jquery.tablesorter.pager.js"></script>
	<script type="text/javascript" charset="utf-8">
		$(window).load(function()
		{
			// REMOTE - GET USER
			$.ajax({
				url: "/remoting/json.php/loRepository.getUser",
				context: document.body,
				dataType: 'json',
				success: function(msg)
					{
						// $('span.first').append(msg.first);
						// $('span.last').append(msg.last);
						getMyLOs();
					}
			});
			
			$("#button-preview").click(function(){
				$('#protostats').submit();
			});
		
			$("#button-download").click(function(){
				downloadCSV();
			});
			
			// REMOTE - GET LEARNING OBJECTS
			function getMyLOs()
			{
				$.ajax({
					url: "/remoting/json.php/loRepository.getLOs/false/true",
					context: document.body,
					dataType: 'json',
					success: onGetMyLOs
				});
			}
		
			// PLACE RESULTS INTO THE SELECT BOX
			function onGetMyLOs(los)
			{
				var loBox = $('#mylos');
				var options = loBox.attr('options');

				// sort alphabetically
				los = $(los).sort(function(a,b){
					if(a.title.toLowerCase() > b.title.toLowerCase())
					{
						return 1
					}
					else if(a.title.toLowerCase() == b.title.toLowerCase())
					{
						return a.version > b.version ? 1 : -1
					}
					else
					{
						return -1
					}
				});
			
				$.each(los, function(text, lo)
				{
					if(lo.version > 0 && lo.subVersion == 0)
					{
						var d = new Date(lo.createTime * 1000);
						options[options.length] = new Option(lo.title + " v." + lo.version + "." + lo.subVersion + ' ' + (d.getMonth()+1) + '/' + d.getDate() + '/' + d.getFullYear(), lo.loID);
					}
				});
			}
		
			// ON SUBMIT
			$('#protostats').submit(function(){
			
				var los = new Array();
				$("#mylos option:selected").each(function(index,val){
					los.push($(this).val());
				});
				los = '['+los.join()+']';
			
				var s = $('#start_date').datepicker('getDate').getTime()/1000;
				var e = $('#end_date').datepicker('getDate').getTime()/1000;
			
				getStats(los, $('input:radio[name=stat]:checked').val(), s, e, $('input:radio[name=resolution]:checked').val());
				return false;
			});
		
			function getStats(los, statID, startDate, endDate, resolution)
			{
				$.ajax({
					url: "/remoting/json.php/loRepository.getLOStats/"+los+'/'+ statID + '/'+ startDate +'/' + endDate + '/' + resolution,
					context: document.body,
					dataType: 'json',
					success: onGetStats
				});
				$('#results-table').remove();
			}
		
			// PLACE RESULTS IN A TABLE
			function onGetStats(results)
			{
				// Clear previous results
				$('#results-table').remove();
				
				// build the table
				$('#results').append('<table id="results-table" class="tablesorter"><thead><tr class="table-header"></tr></thead><tbody></tbody></table>');

				// Build the header row
				for(index in results[0])
				{
					$('#results-table tr.table-header').append('<th>'+index+'</th>');
				};
			
				// Place each data row
				$(results).each(function(index,val){
					var row = '<tr>'
					for(index in val)
					{
						row += '<td>'+ val[index] +'</td>';
					}
					row += '</tr>'
					$('#results-table tbody').append(row);
				});
			
				// Enable the table sorter
				$("#results-table").tablesorter({widthFixed: true, widgets: ['zebra']}).tablesorterPager({container: $("#pager")});
			
			}
			
			// Listen to time radio button clicks
			$("input[name=time]").change(function(){
				if($(this).val() == 'all')
				{
					$("#start_date").datepicker('setDate', new Date(2008, 1 - 1, 1)); // set to obojobo epoch
					$("#end_date").datepicker('setDate', new Date("+1D")); // set to now
					$('#custom-time').hide();
				}
				else
				{
					// Ok, show everything
					$('#custom-time').show();
				}

			});
			
			// SET UP THE DATE PICKERS
			var dates = $( "#start_date, #end_date" ).datepicker({
				defaultDate: "+1w",
				changeMonth: true,
				numberOfMonths: 3,
				minDate: new Date(2008, 1 - 1, 1),
				maxDate: "+1D",
				onSelect: function( selectedDate ) {
					var option = this.id == "start_date" ? "minDate" : "maxDate",
						instance = $( this ).data( "datepicker" ),
						date = $.datepicker.parseDate(
							instance.settings.dateFormat ||
							$.datepicker._defaults.dateFormat,
							selectedDate, instance.settings );
					dates.not( this ).datepicker( "option", option, date );
				}
			});
			$("#start_date").datepicker( "option", "defaultDate", new Date(2008, 1 - 1, 1) );
			$("#end_date").datepicker( "option", "defaultDate", "+1D");
			$("#start_date").datepicker('setDate', new Date(2008, 1 - 1, 1)); // set to obojobo epoch
			$("#end_date").datepicker('setDate', new Date('+1D')); // set to now
		});
		
		function downloadCSV()
		{
			
			var los = new Array();
			$("#mylos option:selected").each(function(index,val){
				los.push($(this).val());
			});
			los = '&los[]='+los.join('&los[]=');
			var statValue = $("input[name=stat]:checked").val();
			var s = $('#start_date').datepicker('getDate').getTime()/1000;
			var e = $('#end_date').datepicker('getDate').getTime()/1000;
			var r = $('input:radio[name=resolution]:checked').val()
			window.open('http://obo/assets/csv.php?function=stats'+los+'&stat='+statValue+'&start='+s+'&end='+e+'&resolution='+r,'_blank');
		}
		
	</script>
	<style type="text/css" media="screen">
		div.ui-datepicker{
		 font-size:10px;
		}
		
		
		#button-preview {
			-moz-box-shadow:inset 0px 1px 0px 0px #bbdaf7;
			-webkit-box-shadow:inset 0px 1px 0px 0px #bbdaf7;
			box-shadow:inset 0px 1px 0px 0px #bbdaf7;
			background-color:#79bbff;
			-moz-border-radius:6px;
			-webkit-border-radius:6px;
			border-radius:6px;
			border:1px solid #84bbf3;
			display:inline-block;
			color:#ffffff;
			font-family:arial;
			font-size:15px;
			font-weight:bold;
			padding:6px 24px;
			text-decoration:none;
			text-shadow:1px 1px 0px #528ecc;
		}
		#button-preview:hover {
			background-color:#378de5;
		}
		#button-preview:active {
			position:relative;
			top:1px;
		}
		
		#button-download {
			-moz-box-shadow:inset 0px 1px 0px 0px #c1ed9c;
			-webkit-box-shadow:inset 0px 1px 0px 0px #c1ed9c;
			box-shadow:inset 0px 1px 0px 0px #c1ed9c;
			background-color:#9dce2c;
			-moz-border-radius:6px;
			-webkit-border-radius:6px;
			border-radius:6px;
			border:1px solid #83c41a;
			display:inline-block;
			color:#ffffff;
			font-family:arial;
			font-size:15px;
			font-weight:bold;
			padding:6px 24px;
			text-decoration:none;
			text-shadow:1px 1px 0px #689324;
		}
		#button-download:hover {
			background-color:#8cb82b;
		}
		#button-download:active {
			position:relative;
			top:1px;
		}
		
		#custom-time
		{
			display:none;
		}
		#form-buttons
		{
			padding:20px;
		}
	</style>
</head>
<body>
<h2>Choose Learning Object(s)</h2>
<form id="protostats" action="protostats_submit" method="get" accept-charset="utf-8">

<select name="some_name" id="mylos" multiple onchange="" size="15"></select><br>

<h2>Choose Stat</h2>
	<input type="radio" name="stat" value="10" id="instance_count" CHECKED><label for="instance_count">10. Total Instances Created</label><br>
	<input type="radio" name="stat" value="20" id="student_count"><label for="student_count">20. Total Views <span style="color:red;">[slow]</span></label><br>
	<input type="radio" name="stat" value="30" id="derivative_count"><label for="derivative_count">30. Total View Time by Section</label><br>
	<input type="radio" name="stat" value="90" id="content_views"><label for="content_views">90. Total Page &amp; Question Views <span style="color:red;">[slow]</span></label><br>
	<input type="radio" name="stat" value="40" id="assessment_count"><label for="assessment_count">40. Total Assessments Completed</label><br>
	<input type="radio" name="stat" value="50" id="import_scores"><label for="import_scores">50. Total Score Import Usage</label><br>
	<input type="radio" name="stat" value="60" id="who_created_instances"><label for="who_created_instances">60. List Who Created Instances</label><br>
	<input type="radio" name="stat" value="65" id="who_created_los"><label for="who_created_los">65. List Learning Object Authors <span style="color:red;">[review needed]</span></label><br>
	<input type="radio" name="stat" value="70" id="which_courses"><label for="which_courses">70. List Which Courses</label><br>
	<input type="radio" name="stat" value="75" id="who_visited"><label for="who_visited">75. Individual Visitors  <span style="color:red;">[slow]</span></label><br>
	<!-- <input type="radio" name="stat" value="80" id="question_answers"><label for="question_answers"><s>Question Answer Values</s></label><br> -->
	<!-- <input type="radio" name="stat" value="100" id="scores"><label for="scores"><s>Scores</s></label><br> -->
	<!-- <input type="radio" name="stat" value="110" id="attempt"><label for="attempt"><s>Attempt</s></label><br> -->
</fieldset>
<h2>Choose Timeframe</h2>
<input type="radio" name="time" value="all" id="time_all" CHECKED><label for="time_all">All Time</label><br>
<input type="radio" name="time" value="custom" id="time_year"><label for="time_year">Custom...</label><br>
<span id="custom-time">
	<label for="start_date">From</label>
	<input type="text" id="start_date" name="start_date"/>
	<label for="end_date">to</label>
	<input type="text" id="end_date" name="end_date"/>
</span>

<h2>Choose Time Resolution</h2>
<input type="radio" name="resolution" value="all" id="resolution_all" CHECKED><label for="resolution_all">All Time</label><br>
<input type="radio" name="resolution" value="year" id="resolution_year"><label for="resolution_year">Years</label><br>
<input type="radio" name="resolution" value="month" id="resolution_month"><label for="resolution_month">Months</label><br>
<input type="radio" name="resolution" value="day" id="resolution_day"><label for="resolution_day">Days</label><br>
<input type="radio" name="resolution" value="hour" id="resolution_hour"><label for="resolution_hour">Hours</label><br>
</form>

<div id="form-buttons">
	<div id="button-preview" href="#bottom"  class="myButton">Preview 10 Rows</div>
	<div id="button-download" href="#"  class="myButton">Download CSV</div>
</div>
<div id="results"></div>
</body>
</html>