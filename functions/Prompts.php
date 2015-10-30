<?php

// TODO Bug Print after Delete OR Delete OK
/**
 * Prompt before Delete
 * and display OK & Cancel buttons
 *
 * @example if ( DeletePrompt( _( 'Title' ) ) ) DBQuery( "DELETE FROM BOK WHERE id='" . $_REQUEST['benchmark_id'] . "'" );
 *
 * @param  string  $title                    Prompt title
 * @param  string  $action                   Prompt action (optional)
 * @param  boolean $remove_modfunc_on_cancel Remove &modufnc=XXX part of the cancel button URL (optional)
 *
 * @return boolean true if user clicks OK or Cancel + modfunc, else false
 */
function DeletePrompt( $title, $action = 'Delete', $remove_modfunc_on_cancel=true )
{
	// display prompt
	if ( ( !isset( $_REQUEST['delete_ok'] )
			|| empty( $_REQUEST['delete_ok'] ) )
		&&  ( !isset( $_REQUEST['delete_cancel'] )
			|| empty( $_REQUEST['delete_cancel'] ) ) )
	{
		// set default action text
		if ( $action === 'Delete' )
			$action = _( 'Delete' );

		echo '<br />';

		$PHP_tmp_SELF = PreparePHP_SELF( $_REQUEST );

		if ( $remove_modfunc_on_cancel )
			$remove = array( 'modfunc' );
		else
			$remove = array();

		$PHP_tmp_SELF_cancel = PreparePHP_SELF( $_REQUEST, $remove, array( 'delete_cancel' => true ) );

		PopTable( 'header', _( 'Confirm' ) . ( mb_strpos( $action, ' ' ) === false ? ' '. $action : '' ) );

		echo '<h4 class="center">' . sprintf( _( 'Are you sure you want to %s that %s?' ), $action, $title ).'</h4>
			<form action="' . $PHP_tmp_SELF . '" method="POST" class="center">' .
				SubmitButton( _( 'OK' ), 'delete_ok' ) .
				'<input type="button" name="delete_cancel" value="' . _( 'Cancel' ) . '" onclick="ajaxLink(\'' . $PHP_tmp_SELF_cancel . '\');" />
			</form>
		</div>';

		PopTable( 'footer' );

		return false;
	}
	// if user clicked OK or Cancel + modfunc
	else
		return true;
}

/**
 * Prompt question to user
 * and display OK & Cancel buttons
 *
 * Go back in browser history on Cancel
 *
 * @example if ( Prompt( _( 'Confirm' ), _( 'Do you want to dance?' ), $message ) )
 *
 * @param  string  $title    Prompt title (optional)
 * @param  string  $question Prompt question (optional)
 * @param  string  $message  Prompt message (optional)
 *
 * @return boolean true if user clicks OK, else false
 */
function Prompt( $title = 'Confirm', $question = '', $message = '' )
{
	// display prompt
	if ( !isset( $_REQUEST['delete_ok'] )
		|| empty( $_REQUEST['delete_ok'] ) )
	{
		// set default title
		if ( $title === 'Confirm' )
			$title = _( 'Confirm' );

		echo '<br />';

		$PHP_tmp_SELF = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $title );

		echo '<h4 class="center">' . $question . '</h4>
			<form action="' . $PHP_tmp_SELF . '" method="POST">' .
				$message .
				'<br /><div class="center">' .
				SubmitButton( _( 'OK' ), 'delete_ok' ) .
				'<input type="button" name="delete_cancel" value="' . _( 'Cancel' ) . '" onclick="javascript:self.history.go(-1);">
				</div><br />
			</form>';

		PopTable( 'footer' );

		return false;
	}
	// if user clicked OK
	else
		return true;
}


/**
 * Prompt message in JS Alert box & close window
 *
 * Use the BackPrompt function only if there is an error
 * in a script opened in a new window (ie. PDF printing)
 * BackPrompt will alert the message and close the window
 *
 * @param  string $message Alert box message
 *
 * @return string JS Alert box & close window, then exits
 */
function BackPrompt( $message )
{
	?>
	<script>
		alert(<?php echo json_encode( $message ); ?>);
		window.close();
	</script>

	<?php exit();
}
