<?php
/*
Extension for Lazymofo adds
- Enable use of bootstrap thru injection of bootstrap classes via jQuery (you still need to add the bootstrap files, an example how-to is TBD
- Allow parent-child tables, through functions to save/restore http variables states via push and popping of variables to save, an example how-to is TBD
*/
// Form SQL field names must follow exact name in DB otherwise cannot update/insert
include_once 'lazy_mofo.php';

class LMX extends lazy_mofo {

// Bootstrap JS
var $bs_js = "
	// a class for clearing input, usage (useful for bootstrap feedback classes): <input /><span class='clearer' />
	$('.form-control-feedback.clearer').css('pointer-events','auto');
	$('.clearer').click(function () { $(this).prev('input').val('').focus(); });

	$('#lm').addClass('table-responsive');
	$('#lm table').addClass('table table-condensed');
	$('#lm .lm_grid_add_link').addClass('btn btn-default btn-sm');
	//$('#lm label').removeClass('checkbox');
	//$('#lm label').removeClass('select');
	//$('#lm label').removeClass('radio');
	$('#lm input[type=button]').addClass('btn btn-default btn-sm');
	$('#lm input[type=submit]').addClass('btn btn-primary btn-sm');
	$('#lm select').addClass('form-control');
	$('#lm form select').css({ 'min-width': '0px', width: 'auto', display: 'inline' });

	$('#lm .lm_form input').addClass('form-control');
	$('#lm .lm_form textarea').addClass('form-control');
	$('#lm .lm_form input[type=radio]').removeClass('form-control');
	$('#lm .lm_search_input').addClass('form-control');
	//$(\"	input:text[name='_search']\").css({ width: '50%' });
	$('#lm .lm_search_box').addClass('form-inline');

	//no need after adding $('#lm form select').css(...) in code above: $('#lm form .lm_pagination select').css({ 'min-width': '0px', width: 'auto', display: 'inline' });
	//$('#lm form .lm_pagination select').removeClass('form-control');
";

var $controls_js = ''; // for additional JS controls e.g. signature_pad, etc.

	function push_get($query_string_list = '_order_by,_desc,_offset,_search,_pagination_off,') {
		$query_string_list .= $this->query_string_list;     // append users additions
		$query_string_list = trim($query_string_list, ' '); 
		$get = '';
		$arr = preg_split('/[, ]+/', $query_string_list);
		foreach($arr as $var)
			if(mb_strlen(@$_REQUEST[$var]) > 0)
				$get .= "&$var=" . urlencode($_REQUEST[$var]);
		return '_p='.urlencode(ltrim($get, '&'));
	}

	function pop_get() {
		return urldecode( @$_REQUEST['_p'] );
	}

	function emsg($_str) { // error display for edit/insert
		return '<span style="color:red;">ERROR: '.$_str.'</span>';
	}

	function insert(){ // Override the Insert Function (unless author changes, then remove this) - add query string

        	// purpose: called from contoller to display insert() data
        
	        $error = '';

	        // validation system
        	$is_valid = $this->validate($this->on_insert_validate);
        	if(!$is_valid)
            		$error = $this->validate_text_general; //optional general error at the top

		// call user function to validate or whatever
	        if($is_valid && $this->on_insert_user_function != '')
		            $error = call_user_func($this->on_insert_user_function);

	        // go back on validation error
	        if($error != '' || !$is_valid){
			$this->edit($error);
        		return;
        	}

		// insert data
        	$id = $this->sql_insert();

		// user function after insert
	        if($this->after_insert_user_function != '')
            		call_user_func($this->after_insert_user_function, $id);
        
        	// send user back to edit screen if desired
	 	$action = '';
        	if($this->return_to_edit_after_insert)
        		$action = 'action=edit&';

        	// redirect user
        	$url = $this->get_uri_path() . "{$action}_success=1&$this->identity_name=$id&" .$this->get_qs(); // AARON - problem here indicated by Ian,
        	// New Record will not appear if a search value which cannot be found even in new record is keyed in...
	 	$this->redirect($url, $id);

	}

	function run(){ // Override this too - allow for "view" to identify readonly form

       	// purpose: built-in controller 

       		switch($this->get_action()){
           		case "view":          $this->edit();        break; // AARON
           		case "edit":          $this->edit();        break;
           		case "insert":        $this->insert();      break;
           		case "update":        $this->update();      break;
           		case "update_grid":   $this->update_grid(); break;
           		case "delete":        $this->delete();      break;
           		default:              $this->index();
       		}
   	}

	function display_error($error, $source_function){ // Override this too
        
		// purpose: display errors to user.
		if (strstr($error,"\narr_sql_param:") != FALSE)  // do not display too much data
			$msg = nl2br($this->clean_out("ERROR: $source_function"));
		else
			$msg = nl2br($this->clean_out("Error: $error\nSent From: $source_function"));
		echo "<div class='lm_error' style='color: red;'>$msg</div>" ;
	}	// UNUSED var $owner; function set_owner ($name) { $this->owner = $name; } // test code

	//To use lazy mofo as a creation form only ?action=edit
	//Usage:
	//	$lm = new LMX($dbh); 
	//	$lm->table = 'abc';
	//	$lm->identity_name = 'Id';
	//	if ( !$lm->is_insert_ui() ) die('Cannot'); // if someone tries something funny
	//	$lm->return_to_edit_after_insert = false;
	//	$lm->after_insert_user_function = 'my_after_insert';
	//
	//	function my_after_insert($id){
	//		if ($id) {
	//			header('location: http://www.google.com/'); // redirect if insert was successful
	//			die(''); // need a die
	//		}
	//	}
	//
	//	$lm->on_insert_user_function = 'my_on_insert';
	//	function my_on_insert(){
	//		//TBD Validation,verification,captcha checking (in case someone tries something funny)
	//	}
	function is_insert_ui() {
		if (($this->get_action() == 'edit' || $this->get_action() == 'insert') && intval(@$_REQUEST[$this->identity_name]) == 0) return true;
		return false;
	}

	function is_form() { // is it form or grid
		if ($this->get_action() == 'view' || $this->get_action() == 'edit') return true;
		return false;
	}

	function is_form_insert() { // is it form insert
		if ( $this->is_form() && intval(@$_REQUEST[$this->identity_name]) == 0) return true;
		return false;
	}
	function is_form_update() { // is it form update
		if ( $this->is_form() && intval(@$_REQUEST[$this->identity_name]) != 0) return true;
		return false;
	}

	// requires - https://github.com/jhuckaby/webcamjs
	// usage - return webcamjs('myid_fieldname', 'data');
	// if value is blank, unset so no save of changes
	private static $webcam_init = false;
	function webcamjs($_id, $_data = '') {
		if (self::$webcam_init) return 'ERROR: ONLY 1 WEBCAM PER FORM';
		self::$webcam_init = true;	

		$this->controls_js .= "
		if (typeof $_id === 'undefined') { 
			Webcam.set({ width: 320, height: 240 }); Webcam.attach( '#{$_id}_cam' );
		} else { alert('ID {$_id} Already Used'); }
		";

		$_style = 'float:left;overflow:hidden;';
		//$_style = '';
		return "
		<div><img src=\"$_data\" width='320' height='240' alt='No Image' style='$_style'></div>&nbsp;
		<div id='{$_id}_cam' onClick='take_pic()' style='$_style'>
			<script language='JavaScript'>
				//Webcam.set({ width: 320, height: 240 }); Webcam.attach( '#{$_id}_cam' );
				function take_pic() {
					if ($('#{$_id}_dat').val() == '') {
						Webcam.snap( function(data_uri) { 
							$('#{$_id}_dat').val( data_uri ); 
							//$('#{$_id}_dat').val( data_uri.replace(/^data\:image\/\w+\;base64\,/, '') ); 
						} ); Webcam.freeze();
					} else { Webcam.unfreeze(); $('#{$_id}_dat').val(''); }
				}
			</script>
		</div>
		<input id='{$_id}_dat' name='{$_id}' type='hidden' value=''/>
		";
	}

	// requires - https://github.com/szimek/signature_pad
	// usage in php - return signature_pad('myid_fieldname', 'my_name', [data optional]);
	// usage in js - in document ready (echo one time only, if already echoed, do not echo again): echo $lm->controls_js;
	// if value is blank, unset so no save of changes
	private static $signature_pad_init = false;
	function signature_pad($_id, $_name = '', $_data = '') {
		if (self::$signature_pad_init) return 'ERROR: ONLY 1 SIGNATURE PER FORM';
		self::$signature_pad_init = true;	
		if ($_name == '') $_name = $_id;
		$this->controls_js .= "
		if (typeof $_id === 'undefined') { 
			{$_id} = new SignaturePad( $('#{$_id}_pad')[0].querySelector('canvas') );
			{$_id}.fromDataURL('$_data');
		} else { alert('ID {$_id} Already Used'); }
		";

		//if ($_data) $_data = "{$_id}.fromDataURL('$_data');";
		//if ($_data) $_img = "<img src=\"$_data\" style='border:1px solid black' width='300' height='150' alt='No Image'/>";
		$str = ''
			."<button class='btn btn-sm btn-danger' onclick=\"$('#{$_id}_dat').val('');{$_id}.clear();\" type='button' style='float:left;margin-right:8px;margin-bottom:8px;'>Clear</button>"
			//.$_img
			."<div id='{$_id}_pad' tabindex='0' onblur=\"$('#{$_id}_dat').val( {$_id}.toDataURL() );\">"
			.'<canvas style="background:#ddd;"></canvas>'
			.'</div>'
			."<input type='hidden' id='{$_id}_dat' name='{$_name}' value='{$_data}'/>"
			;
		return $str;
	}

	// requires = https://github.com/xdan/datetimepicker
	// usage php - return datetime_xdan(column_name, 'yyyy-mm-dd HH:MM');
	// usage in js - in document ready (echo one time only, if already echoed, do not echo again): echo $lm->controls_js;
	function datetime_xdan($column_name, $val) {
		$this->controls_js .= "$('input.lm_{$column_name}').datetimepicker({format:'Y-m-d H:i', step: 30, defaultTime:'10:00', allowBlank: false } );";

		$str = "<div class='input-group'>"
			."<input class='lm_{$column_name}' type='text' name='$column_name' value='$val' />"
			."<span class='input-group-btn'><button class='btn btn-default' type='button' onclick=\"$('input.lm_$column_name').val('');\">"
			."<span class='glyphicon glyphicon-remove' aria-hidden='true'></span>"
			."</button></span>"
			."</div>"
			;
		return $str;
	}
}

// Additional Useful Functions
function grid_output_checkbox($column_name, $value, $command, $called_from) {
	if ($value) return '<input type="checkbox" checked disabled />';
	return '<input type="checkbox" disabled />';
}

?>
